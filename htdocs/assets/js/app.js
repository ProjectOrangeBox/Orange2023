/**
 * 
 */

// create the application object
const app = {
    // html element prefix tag
    elementTag: 'sd-',
    // root application DOM element
    rootElement: undefined,
    // app "storage" space
    storage: {},
    // attach these to rv-click buttons ie. rv-click="actions.localModal"
    actions: {
        loadModal() {
            app.methods.loadModal(getTags(this));
        },
        redirect() {
            app.methods.redirect(getTags(this));
        },
        cancel() {
            app.methods.cancel(getTags(this));
        },
        submit() {
            app.methods.submit(getTags(this));
        },
    },
    // shared application methods
    methods: {
        // load modal template
        loadModal(tags) {
            let modalOptions = JSON.parse(tags['modal-options'] || '{}');
            // pass thru to modal object
            modal.load({ ...tags, ...modalOptions });
        },
        // redirect to another url button
        redirect(tags) {
            tags.redirect = tags.url;
            app.methods.actionBasedOnTags(tags);
        },
        // handle a cancel button
        cancel(tags) {
            app.methods.actionBasedOnTags(tags);
        },
        // submit a form
        submit(tags) {
            for (let httpMethod of ['get', 'put', 'post', 'patch', 'delete', 'options', 'header']) {
                if (tags[httpMethod + '-url']) {
                    tags.httpMethod = httpMethod;
                    tags.url = tags[httpMethod + '-url'];
                    continue;
                }
            }

            // get the payload for the http call from app based on the property tag
            let payload = getProperty(app, tags.property);

            app.methods.makeAjaxCall({
                // get the url to post to with # replacement from the objects uid
                url: app.methods.makeUrl(tags.url, payload.uid),
                // what http method should we use
                type: tags.httpMethod,
                // what should we send as "data"
                data: JSON.stringify(payload),
                // when the request is "complete"
                complete: function (jqXHR) {
                    // capture the text and/or json response
                    let json = jqXHR.responseJSON;

                    tags.jqXHR = jqXHR;
                    tags.json = json;

                    // based on the responds code
                    switch (jqXHR.status) {
                        case 200:
                            // 200 in this case is NOT a valid response code
                            app.methods.alert('200 is an invalid response.');
                            break;
                        case 201:
                            // Created
                            app.methods.actionBasedOnTags(tags);
                            break;
                        case 202:
                            // Accepted
                            app.methods.actionBasedOnTags(tags);
                            break;
                        case 406:
                            // Not Acceptable
                            app.methods.notAcceptable(tags);
                            break;
                        default:
                            // anything other reponds code is an error
                            app.methods.alert('Record Access Issue.');
                    }
                }
            })
        },

        // default not accepted form submission
        notAcceptable(tags) {
            // tag the ui element based on the keys (array) if available 
            if (tags.json.keys) {
                // add the highlights if we can
                app.gui.highlightErrorFields(tags.json.keys);
            }
            // show error dialog
            app.gui.showErrorDialog(tags);
        },

        actionBasedOnTags(tags) {
            // capture the data from the html element (this) which the method was triggered
            // hide any modals which might be on screen
            modal.hide();

            if (tags['on-success-redirect']) {
                tags.redirect = tags['on-success-redirect'];
            }

            if (tags['on-success-refresh']) {
                tags.reload = false;
                tags.redirect = false;

                tags.refresh = true;
            }

            // if reload then reload this location (url) data-reload=""
            if (tags.reload) {
                location.reload();
            }

            // if refresh then refresh the page data-refresh=""
            if (tags.refresh) {
                app.methods.autoLoad();
            }

            // redirect if appropriate
            if (tags.redirect) {
                window.location.href = app.methods.makeUrl(tags.redirect, tags.uid);
            }
        },

        // load a model into the application property
        model(tags, thenCall) {
            let appProperty = tags.property || 'record';
            let jsonProperty = tags.modelProperty || undefined;

            // make ajax request
            app.methods.makeAjaxCall({
                url: app.methods.makeUrl(tags.model),
                type: tags.method || 'get',
                complete: function (jqXHR) {
                    // based on the responds code
                    if (jqXHR.status == 200) {
                        // success

                        // capture the text or json from the responds
                        let json = jqXHR.responseJSON;

                        // replace the application property with the matching json property
                        if (json) {
                            setProperty(app, appProperty, (jsonProperty) ? getProperty(json, jsonProperty) : json);

                            if (typeof thenCall === 'function') {
                                thenCall(tags);
                            }
                        } else {
                            app.methods.alert('JSON Response Undefined.');
                        }
                    } else {
                        // show error dialog
                        app.methods.alert('Model Access Issue [' + jqXHR.status + '].');
                    }
                }
            });
        },

        // load a template from the server
        template(tags, thenCall) {
            app.methods.makeAjaxCall({
                url: app.methods.makeUrl(tags.template, tags.uid),
                type: tags.method || 'get',
                complete: function (jqXHR) {
                    if (jqXHR.status == 200) {
                        // success
                        // replace DOM Element with responds json or html
                        app.methods.replaceElement(tags.element, jqXHR);

                        if (typeof thenCall === 'function') {
                            thenCall(tags);
                        }
                    } else {
                        // show error dialog
                        app.methods.alert('template Access Issue.');
                    }
                }
            });
        },

        // load a template then load a model
        templateModel(tags) {
            // grab a template and then a model
            app.methods.template(tags, function () {
                app.methods.model(tags)
            });
        },

        replaceElement(element, jqXHR) {
            if (typeof element === 'string') {
                element.innerHTML = document.getElementById(app.methods.removeSelector(elementId));
            }

            let html = jqXHR.responseText || '';

            if (jqXHR.responseJSON) {
                html = jqXHR.responseJSON.html || html;
            }

            element.innerHTML = html;
        },

        // auto load a models where data-autoload = true
        // using the data attached
        autoLoad() {
            for (let tag of ['preload', 'autoload', 'postload']) {
                $('#' + app.rootElement.id + ' [' + app.elementTag + tag + '="true"]').each(function () {
                    let tags = getTags(this);

                    if (tags.template && tags.model) {
                        app.methods.template(tags, app.methods.model(tags));
                    } else if (tags.template) {
                        app.methods.template(tags);
                    } else if (tags.model) {
                        app.methods.model(tags);
                    }
                });
            }
        },
        // convert input array to html for display
        // this removes hard coded html from the server
        wrap(elements, preEach, postEach, preAll, postAll, index) {
            // set up built output
            string = '';

            // setup defaults
            preEach = preEach || '';
            postEach = postEach || '';
            preAll = preAll || '';
            postAll = postAll || '';

            // the index to use from the array
            index = index || 'text';

            // if elements is not an array then make it one
            if (!Array.isArray(elements)) {
                elements = [elements];
            }

            // for each element wrap it with preEach & postEach
            elements.forEach(function (element) {
                // get the index if it's an array or just the element itself if it is not
                // do the actual wrapping
                string += preEach + (element[index] || element) + postEach;
            })

            // wrap the built string
            return preAll + string + postAll;
        },

        // unified method to make an ajax call
        makeAjaxCall(request) {
            // the ajax call defaults
            let defaults = {
                // The type of data that you're expecting back from the server.
                dataType: 'json',
                // When sending data to the server, use this content type.
                contentType: 'application/json; charset=utf-8',
                // Request Method
                type: (request.type ? request.type : 'get'),
            };

            // merge down the defaults
            $.ajax({ ...defaults, ...request });
        },
        // make a url replacing # with a passed id
        makeUrl(url, uid) {
            return url.replace('#', uid || '');
        },
        // remove a selector from a string if it exists
        removeSelector(string, selector) {
            selector = selector || '#';

            return string.replace(selector, '');
        },
        // add a selector to the beginning of a string if it doesn't have one already
        addSelector(string, selector) {
            selector = selector || '#';

            return selector + string.replace(selector, '');
        },
        // bootbox wrapper
        alert(record) {
            // show the alert
            bootbox.alert(record);
        },
    },
    // gui methods
    gui: {
        // ie. app.gui.highlightErrorFields(json.keys);
        // <array>invalidNames
        // ["firstname","lastname"]
        highlightErrorFields(invalidNames) {
            // first remove all that might be on the screen now
            $('#' + app.rootElement.id + ' .is-invalid').removeClass('is-invalid');

            // then loop over the array and add the is-invalid class to
            // each matching html form element by name
            invalidNames.forEach(function (name) {
                // where should we look for this data-autoload elements?
                // find the matching form elements
                $('#' + app.rootElement.id + ' [name="' + name + '"]').addClass('is-invalid');
            });
        },
        // ie. app.gui.showErrorDialog(json);
        showErrorDialog(tags) {
            let record = tags.json;

            // this responds is json so we grab the values or use the defaults provided
            // https://bootboxjs.com/documentation.html
            record.size = record.size || 'large'; // large alert
            record.title = record.title || 'Your Form Has The Following Errors'; // default alert title
            record.centerVertical = record.centerVertical || true; // default center vertically
            record.closeButton = record.closeButton || false; // default hide close button
            record.wrapPrefix = record.wrapPrefix || '<i class="fa-solid fa-triangle-exclamation"></i> '
            record.wrapSuffix = record.wrapSuffix || '</br>';

            // format the json errors (array) 
            // here not on the server for display in the next step
            record.message = app.methods.wrap(record.errors, record.wrapPrefix, record.wrapSuffix);

            // show the bootbox alert
            app.methods.alert(record);
        },
    }
} // end app object

/* bootstrap tinybind app */
document.addEventListener('DOMContentLoaded', function () {
    // setup the bootstrap modal reference
    modal.init();

    app.rootElement = document.getElementById('app');

    tinybind.bind(app.rootElement, app);

    // detect and load models
    app.methods.autoLoad();
});

// create global function setProperty
function setProperty(object, path, value) {
    if (typeof path === 'string') {
        path = path.split('.');
    }

    if (path.length === 1) object[path[0]] = value;
    else if (path.length === 0) throw error;
    else {
        if (object[path[0]])
            return setProperty(object[path[0]], path.slice(1), value);
        else {
            object[path[0]] = {};
            return setProperty(object[path[0]], path.slice(1), value);
        }
    }
};

function getProperty(obj, path) {
    const properties = path.split('.');
    let value = obj;
    for (const prop of properties) {
        if (value && typeof value === 'object' && value.hasOwnProperty(prop)) {
            value = value[prop];
        } else {
            return undefined;
        }
    }
    return value;
}

function getTags(element, tag) {
    tag = tag || app.elementTag;

    let reg = new RegExp('^' + tag, 'i'); //case insensitive mce_ pattern
    let arr = {};

    for (const attr of element.attributes) {
        if (reg.test(attr.name)) { //if an attribute starts with ...
            arr[attr.name.substr(tag.length)] = attr.value; //push to collection
        }
    }

    // add the element to the tags
    arr.element = element;

    return arr;
}


// create the modal object
const modal = {
    open: false, // modal is open
    id: '', // modal id as string
    jid: '', // modal id as jquery selector by id 
    ref: undefined, // reference to modal
    config: {},
    init(config) {
        // save the config
        this.config = config || {};

        // unique id
        this.id = 'modal-AEDFCA';
        this.jid = '#' + this.id;

        // add the html to the page
        this.appendHTMLto(this.id, document.body);
    },
    // Append the modal to the DOM
    appendHTMLto(id, element) {
        // Create the Modal Elements
        let element_a = document.createElement('div');
        let element_b = document.createElement('div');
        let element_c = document.createElement('div');
        let element_d = document.createElement('div');

        // Set Attributes
        element_a.setAttribute('id', id);
        element_a.setAttribute('class', 'modal fade');
        element_a.setAttribute('tabindex', '-1');

        element_b.setAttribute('class', 'modal-dialog modal-dialog-scrollable modal-dialog-centered');

        element_c.setAttribute('class', 'modal-content');

        element_d.setAttribute('id', id + '-content');
        element_d.setAttribute('class', 'modal-body');

        // Append Children
        element.appendChild(element_a);
        element_a.appendChild(element_b);
        element_b.appendChild(element_c);
        element_c.appendChild(element_d);
    },
    // resize modal
    resize(size) {
        // resize modal (we only have 1 so it is by id) or default to large
        $(this.jid).removeClass('modal-xl modal-lg modal-md modal-sm').addClass(size || 'modal-lg');
    },
    // put the returned html into the modal
    content(html) {
        document.getElementById(this.id + '-content').innerHTML = html;
    },
    // load into a bootstrap modal
    load(tags) {
        // load into a bootstrap modal (not model!)
        // capture the data from the html element (this) which the method was triggered
        // rv-on-click="methods.loadModal"
        // data-foo="123" data-bar="abc" 
        app.methods.makeAjaxCall({
            url: app.methods.makeUrl(tags['modal-template'], tags.id),
            type: tags.method,
            complete: function (jqXHR) {
                // if the responds status is
                if (jqXHR.status == 200) {
                    // success

                    // resize modal (we only have 1 so it is by id) or default to large
                    modal.resize(tags.size);

                    // put the returned html into the modal
                    modal.content(jqXHR.responseText);

                    // rebind the modal which now contain the new html
                    tinybind.bind(document.getElementById(modal.id), app);

                    // load a record into the modal
                    app.methods.autoLoad();

                    // show the modal
                    modal.show();
                } else {
                    // any other response code displays a error
                    app.methods.alert('Could not load modal.');
                }
            },
        });
    },
    // hide the modal
    hide() {
        if (this.open) {
            // hide it
            this.ref.hide();
            this.content('');
            this.open = false;
        }
    },
    // show the modal
    show() {
        if (!this.ref) {
            this.ref = new bootstrap.Modal(this.jid, this.config);
        }

        this.ref.show();
        this.open = true;
    },
};
