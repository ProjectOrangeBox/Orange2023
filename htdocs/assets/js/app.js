/**
 * 
 */

// create the application object
const app = {
    // root application DOM element
    rootElement: undefined,
    // app "storage" space
    storage: {},
    // attach these to rv-click buttons ie. rv-click="actions.localModal"
    actions: {
        loadModal() {
            app.methods.loadModal({ ...arguments, ...getAttr(this) });
        },
        redirect() {
            app.methods.redirect({ ...arguments, ...getAttr(this) });
        },
        cancel() {
            app.methods.cancel({ ...arguments, ...getAttr(this) });
        },
        submit() {
            app.methods.submit({ ...arguments, ...getAttr(this) });
        },
        close() {
            // only works on modals
            modal.hide();
        }
    },
    // shared application methods
    methods: {
        // load modal template
        loadModal(args) {
            args.templateUrl = app.methods.makeUrl(args[0], args[1]);
            args.options = JSON.parse(args['modal-options'] || '{}');

            modal.load(args);
        },
        // redirect to another url button
        redirect(args) {
            args.redirect = app.methods.makeUrl(args[0], args[1]);

            app.methods.actionBasedOnArgument(args);
        },
        // handle a cancel button
        cancel(args) {
            app.methods.actionBasedOnArgument(args);
        },
        // submit a form
        submit(args) {
            // get the payload for the http call from app based on the property tag
            let url = app.methods.makeUrl(args[1], args[2]);

            app.methods.makeAjaxCall({
                // get the url to post to with # replacement from the objects uid
                url: url,
                // what http method should we use
                type: args[0],
                // what should we send as "data"
                data: JSON.stringify(args[3]),
                // when the request is "complete"
                complete: function (jqXHR) {
                    // capture the text and/or json response
                    let json = jqXHR.responseJSON;

                    args.jqXHR = jqXHR;
                    args.json = json;

                    // based on the responds code
                    switch (jqXHR.status) {
                        case 200:
                            // 200 in this case is NOT a valid response code
                            app.methods.alert('200 is an invalid response.');
                            break;
                        case 201:
                            // Created
                            app.methods.actionBasedOnArgument(args);
                            break;
                        case 202:
                            // Accepted
                            app.methods.actionBasedOnArgument(args);
                            break;
                        case 406:
                            // Not Acceptable
                            app.methods.notAcceptable(args);
                            break;
                        default:
                            // anything other reponds code is an error
                            app.methods.alert('Record Access Issue.');
                    }
                }
            })
        },

        // default not accepted form submission
        notAcceptable(args) {
            // tag the ui element based on the keys (array) if available 
            if (args.json.keys) {
                // add the highlights if we can
                app.gui.highlightErrorFields(args.json.keys);
            }
            // show error dialog
            app.gui.showErrorDialog(args);
        },

        actionBasedOnArgument(args) {
            // capture the data from the html element (this) which the method was triggered
            // hide any modals which might be on screen
            modal.hide();

            if (args['on-success-redirect'] || false) {
                args.reload = false;
                args.refresh = false;
                args.redirect = args['on-success-redirect'];
            }

            if (args['on-success-refresh'] || false) {
                args.reload = false;
                args.redirect = false;
                args.refresh = true;
            }

            // if reload then reload this location (url) data-reload=""
            if (args.reload || false) {
                location.reload();
            }

            // if refresh then refresh the page data-refresh=""
            if (args.refresh || false) {
                app.methods.autoLoad();
            }

            // redirect if appropriate
            if (args.redirect || false) {
                window.location.href = args.redirect;
            }
        },

        // load a model into the application property
        model(args, thenCall) {
            let appProperty = args.property || 'record';
            let jsonProperty = args.modelProperty || undefined;
            let url = app.methods.makeUrl(args.model);

            // make ajax request
            app.methods.makeAjaxCall({
                url: url,
                type: args.method || 'get',
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
                                thenCall(args);
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
        template(args, thenCall) {
            let url = app.methods.makeUrl(args.template, args.uid || 0);
            
            app.methods.makeAjaxCall({
                url: url,
                type: args.method || 'get',
                complete: function (jqXHR) {
                    if (jqXHR.status == 200) {
                        // success
                        // replace DOM Element with responds json or html
                        app.methods.replaceElement(args.element, jqXHR);

                        if (typeof thenCall === 'function') {
                            thenCall(args);
                        }
                    } else {
                        // show error dialog
                        app.methods.alert('template Access Issue.');
                    }
                }
            });
        },

        // load a template then load a model
        templateModel(args) {
            // grab a template and then a model
            app.methods.template(args, function () {
                app.methods.model(args)
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
                $('#' + app.rootElement.id + ' [' + tag + '="true"]').each(function () {
                    let args = getAttr(this);

                    if (args.template && args.model) {
                        app.methods.template(args, app.methods.model(args));
                    } else if (args.template) {
                        app.methods.template(args);
                    } else if (args.model) {
                        app.methods.model(args);
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
    init(tinybind);

    // setup the bootstrap modal reference
    modal.init();

    app.rootElement = document.getElementById('app');

    tinybind.bind(app.rootElement, app);

    // detect and load models
    app.methods.autoLoad();
});

function init(tinybind) {
    tinybind.formatters.args = function (target) {
        var args = Array.prototype.slice.call(arguments);
        args.splice(0, 1);

        return function (evt) {
            var cpy = args.slice();
            Array.prototype.push.apply(cpy, Array.prototype.slice.call(arguments));
            return target.apply(this, cpy);
        };
    };
}

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
    load(args) {
        // load into a bootstrap modal (not model!)
        // capture the data from the html element (this) which the method was triggered
        // rv-on-click="methods.loadModal"
        // data-foo="123" data-bar="abc" 
        app.methods.makeAjaxCall({
            url: args.templateUrl,
            type: args.method || 'get',
            complete: function (jqXHR) {
                // if the responds status is
                if (jqXHR.status == 200) {
                    // success

                    // resize modal (we only have 1 so it is by id) or default to large
                    modal.resize(args.options.size || 'xl');

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

function getAttr(that) {
    let args = {};

    for (a of that.attributes) {
        args[a.name] = a.value;
    }

    return args;
}
