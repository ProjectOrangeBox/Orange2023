class Bound {
    constructor() { };
    attach(dotNotation) {
        const properties = dotNotation.split('.');

        let current = this;

        for (let i = 0; i < properties.length - 1; i++) {
            const prop = properties[i];

            if (current[prop] === undefined || current[prop] === null) {
                current[prop] = {};
            }
            current = current[prop];
        }

        current[properties[properties.length - 1]] = {};
    }
}




// create the application object
class App {
    // root application DOM element
    rootElement = undefined;
    // app "storage" space
    storage = {};
    // modal storage
    modals = {};
    // local cache
    cache = {};

    constructor(id, tinybind, models) {
        this.rootElement = document.getElementById(id);

        tinybind.bind(this.rootElement, models);

        this.autoLoad();
    };

    // rv-click="actions.localModal | args 'name' '/foobar/template'"
    actions = {
        parent: this,

        loadModal(name, templateUrl) {
            app.methods.loadModal({ "element": this, "name": name, "templateUrl": templateUrl, ...getAttr(this) });
        },
        redirect(url) {
            app.methods.redirect({ "element": this, "url": url, ...getAttr(this) });
        },
        cancel() {
            app.methods.cancel({ "element": this, ...arguments, ...getAttr(this) });
        },
        submit(httpmethod, url, record) {
            app.methods.submit({ "element": this, "httpmethod": httpmethod, "url": url, "record": record, ...getAttr(this) });
        },
        close(name) {
            app.methods.closeModal(name);
        },
    };


    // load modal template
    loadModal(args) {
        args.templateUrl = app.methods.makeUrl(args.templateUrl, args.element);
        args.options = JSON.parse(args['modal-options'] || '{}');

        app.methods.openModal(args.name, args).load(args);
    };

    // redirect to another url button
    redirect(args) {
        args.redirect = app.methods.makeUrl(args.url, args.id);

        app.methods.actionBasedOnArguments(args);
    };

    // handle a cancel button
    cancel(args) {
        app.methods.actionBasedOnArguments(args);
    };

    // submit a form
    submit(args) {
        // get the payload for the http call from app based on the property tag
        let url = app.methods.makeUrl(args.url, args.id);

        makeAjaxCall(this, {
            // get the url to post to with # replacement from the objects uid
            url: url,
            // what http method should we use
            type: args.httpmethod,
            // what should we send as "data"
            data: JSON.stringify(args.record),
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
                        app.methods.actionBasedOnArguments(args);
                        break;
                    case 202:
                        // Accepted
                        app.methods.actionBasedOnArguments(args);
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
    };

    // default not accepted form submission
    notAcceptable(args) {
        // tag the ui element based on the keys (array) if available 
        if (args.json.keys) {
            // add the highlights if we can
            app.gui.highlightErrorFields(args.json.keys);
        }
        // show error dialog
        app.gui.showErrorDialog(args);
    };

    actionBasedOnArguments(args) {
        // capture the data from the html element (this) which the method was triggered
        // hide any modals which might be on screen

        if (args['on-success-close-modal'] || false) {
            app.methods.closeModal(args['on-success-close-modal']);
        }

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
    };

    // load a model into the application property
    model(args, thenCall) {
        let appProperty = args.property || 'record';
        let jsonProperty = args.modelProperty || undefined;
        let url = app.methods.makeUrl(args.model, args);

        // make ajax request
        makeAjaxCall(this, {
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
                        let value = jsonProperty ? getProperty(json, jsonProperty) : json;

                        setProperty(app, appProperty, value);

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
    };

    // load a template from the server
    template(args, thenCall) {
        let url = app.methods.makeUrl(args.template, args.uid || 0);

        makeAjaxCall(this, {
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
    };

    // load a template then load a model
    templateModel(args) {
        // grab a template and then a model
        app.methods.template(args, function () {
            app.methods.model(args)
        });
    };

    replaceElement(element, jqXHR) {
        if (typeof element === 'string') {
            element.innerHTML = document.getElementById(app.methods.removeSelector(elementId));
        }

        let html = jqXHR.responseText || '';

        if (jqXHR.responseJSON) {
            html = jqXHR.responseJSON.html || html;
        }

        element.innerHTML = html;
    };

    // auto load a models where data-autoload = true
    // using the data attached
    autoLoad(selectorId) {
        let id = selectorId || this.parent.rootElement;

        console.log('>>>', id);

        for (let tag of ['preload', 'autoload', 'postload']) {
            $('#' + id + ' [' + tag + '="true"]').each(function () {
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
    };

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
    };

    // make a url replacing # with a passed id
    makeUrl(url, args) {
        let urlReplacements = getTags(args, 'url-');

        for (const key in urlReplacements) {
            url.replace(key, urlReplacements[key]);
        }

        return url;
    };

    // remove a selector from a string if it exists
    removeSelector(string, selector) {
        selector = selector || '#';

        return string.replace(selector, '');
    };

    // add a selector to the beginning of a string if it doesn't have one already
    addSelector(string, selector) {
        selector = selector || '#';

        return selector + string.replace(selector, '');
    };

    addClass(selector, classes) {
        // !todo remove jquery dep.
        $(selector).addClass(classes);

        return app.methods;
    };

    removeClass(selector, classes) {
        // !todo remove jquery dep.
        $(selector).removeClass(classes);

        return app.methods;
    };

    // bootbox wrapper
    alert(record) {
        // show the alert
        bootbox.alert(record);
    };

    openModal(name, config) {
        if (app.modals[name] === undefined) {
            app.modals[name] = new Modal(name, config);
        }

        return app.modals[name];
    };

    closeModal(name) {
        app.modals[name].hide();
    };

    // gui methods
    gui = {
        parent: this,

        // ie. app.gui.highlightErrorFields(json.keys);
        // <array>invalidNames
        // ["firstname","lastname"]
        highlightErrorFields(invalidNames) {
            // first remove all that might be on the screen now
            app.methods.removeClass('#' + app.rootElement.id + ' .is-invalid', 'is-invalid');

            // then loop over the array and add the is-invalid class to
            // each matching html form element by name
            invalidNames.forEach(function (name) {
                // where should we look for this data-autoload elements?
                // find the matching form elements
                app.methods.addClass('#' + app.rootElement.id + ' [name="' + name + '"]', 'is-invalid');
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
    };
} // end app class

// create the modal object
class Modal {
    // modal is open
    open = false;
    // modal id as string
    id = '';
    // modal id as jquery selector by id
    jid = '';
    // reference to modal
    ref = undefined;
    // store the config sent in
    config = {};
    // html loaded?
    htmlLoaded = false;

    name = '';

    constructor(name, config) {
        // save the config
        this.config = config || {};

        this.name = name;

        // unique id
        this.id = 'modal-bootstrap-' + Math.round(Math.random() * (9999999 - 1000000) + 1000000);
        this.jid = '#' + this.id;

        // add the html to the page
        this.appendHTMLto(this.id, document.body);
    }

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
    }

    // resize modal
    resize(size) {
        // resize modal (we only have 1 so it is by id) or default to large
        app.methods.removeClass(this.jid, 'modal-xl modal-lg modal-md modal-sm').addClass(this.jid, size || 'modal-lg');
    }

    // put the returned html into the modal
    content(html) {
        document.getElementById(this.id + '-content').innerHTML = html;
    }

    // load into a bootstrap modal
    load(args) {
        let modal = this;

        if (modal.htmlLoaded) {
            // load a record into the modal
            app.methods.autoLoad(modal.id);

            // show the modal
            modal.show();
        } else {
            makeAjaxCall(this, {
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

                        modal.htmlLoaded = true;

                        // rebind the modal which now contain the new html
                        tinybind.bind(document.getElementById(modal.id), app);

                        // load a record into the modal
                        app.methods.autoLoad(modal.id);

                        // show the modal
                        modal.show();
                    } else {
                        // any other response code displays a error
                        app.methods.alert('Could not load modal.');
                    }
                },
            });
        }
    }

    // hide the modal
    hide() {
        if (this.open) {
            this.ref.hide();
            this.open = false;
        }
    }

    // show the modal
    show() {
        if (!this.ref) {
            this.ref = new bootstrap.Modal(this.jid, this.config);
        }

        this.ref.show();
        this.open = true;
    }
}

// need to remove jquery dep.
function makeAjaxCall(that, request) {
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
}

class loader {
    model(modelUrl, appProperty, modelProperty, options, thenCall) {
        makeAjaxCall(this, {
            url: modelUrl,
            type: options.method || 'get',
            complete: function (jqXHR) {
                // based on the responds code
                if (jqXHR.status == 200) {
                    // success

                    // capture the text or json from the responds
                    let json = jqXHR.responseJSON;

                    // replace the application property with the matching json property
                    if (json) {
                        let value = modelProperty ? getProperty(json, modelProperty) : json;

                        setProperty(app, appProperty, value);

                        if (typeof thenCall === 'function') {
                            thenCall(arguments);
                        }
                    } else {
                        app.methods.alert('Could not load model.');
                    }
                } else {
                    // show error dialog
                    app.methods.alert('Model returned the status [' + jqXHR.status + '].');
                }
            }
        });
    }

    template(templateUrl, elementId, options, thenCall) {
        makeAjaxCall(this, {
            url: templateUrl,
            type: options.method || 'get',
            complete: function (jqXHR) {
                if (jqXHR.status == 200) {
                    // success
                    // replace DOM Element with responds json or html
                    app.methods.replaceElement(elementId, jqXHR);

                    if (typeof thenCall === 'function') {
                        thenCall(args);
                    }
                } else {
                    // show error dialog
                    app.methods.alert('Could not load template.');
                }
            }
        });
    }

    templateModel(templateUrl, elementId, ModelUrl, appProperty, modelProperty, options, thenCall) {
        this.template(templateUrl, elementId, options, this.model(ModelUrl, appProperty, modelProperty, options, thenCall));
    }
}


// global set and get using dot notation in a string
function setProperty(obj, path, value) {
    const properties = path.split('.');
    let current = obj;
    for (let i = 0; i < properties.length - 1; i++) {
        const prop = properties[i];

        if (current[prop] === undefined || current[prop] === null) {
            current[prop] = {};
        }
        current = current[prop];
    }
    current[properties[properties.length - 1]] = value;
}

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

// global capture all attributes on a element
function getAttr(that) {
    let args = {};

    for (a of that.attributes) {
        args[a.name] = a.value;
    }

    return args;
}

// get all attributes starting with a tag ie foo-
function getTags(element, tag) {
    tag = tag || app.elementTag;

    let reg = new RegExp('^' + tag, 'i'); //case insensitive mce_ pattern
    let arr = {};
    let attributes = element.attributes ? element.attributes : element;
    console.log(attributes);
    for (const attr in attributes) {
        console.log(attr);
        if (reg.test(attr)) { //if an attribute starts with ...
            let key = attr.substr(tag.length);
            arr[key] = attributes[attr]; //push to collection
        }
    }

    // add the element to the tags
    arr.element = element;

    return arr;
}

/* bootstrap tinybind app */
document.addEventListener('DOMContentLoaded', function () {
    var app = new App('app', tinybind, new Bound());
});
