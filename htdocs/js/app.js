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
    appendHTMLto(id, element) {
        // Create Elements
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
    resize(size) {
        // resize modal (we only have 1 so it is by id) or default to large
        $(this.jid).removeClass('modal-xl modal-lg modal-md modal-sm').addClass(size || 'modal-lg');
    },
    content(html) {
        // put the returned html into the modal
        document.getElementById(this.id + '-content').innerHTML = html;
    },
    load(data) {
        // load into a bootstrap modal (not model!)
        // capture the data from the html element (this) which the method was triggered
        // rv-on-click="methods.loadModal"
        // data-foo="123" data-bar="abc" 
        app.methods.makeAjaxCall({
            url: app.methods.makeUrl(data.modal, data.id),
            type: data.method,
            complete: function (jqXHR) {
                // if the responds status is
                if (jqXHR.status == 200) {
                    // success

                    // resize modal (we only have 1 so it is by id) or default to large
                    modal.resize(data.size);

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
    hide() {
        if (this.open) {
            // hide it
            this.ref.hide();
            this.content('');
            this.open = false;
        }
    },
    show() {
        if (!this.ref) {
            this.ref = new bootstrap.Modal(this.jid, this.config);
        }

        this.ref.show();
        this.open = true;
    },
};

const app = {
    rootElement: undefined,
    bound: undefined,
    storage: {},
    records: [],
    record: {},
    // attach these to rv-click buttons
    actions: {
        loadModal() {
            app.methods.loadModal(this.dataset);
        },
        redirect() {
            app.methods.redirect(this.dataset);
        },
        cancel() {
            app.methods.cancel(this.dataset);
        },
        submit() {
            app.methods.submit(this.dataset);
        },
    },
    methods: {
        loadModal(data) {
            modal.load(data);
        },
        // redirect to another url button
        redirect(data) {
            // replace # with the data-id=""
            window.location.href = app.methods.makeUrl(data.redirect, data.id);
        },
        // handle a cancel button
        cancel(data) {
            app.methods.refreshOnData({}, data);
        },
        // submit a form
        submit(data) {
            // capture the data from the html element (this) which the method was triggered
            // convert the data-form="" element to a json object
            let key = data.property || 'record';

            app.methods.makeAjaxCall({
                // get the url to post to with # replacement from the form's id
                url: app.methods.makeUrl(data.url, app[key].id),
                type: data.type,
                data: JSON.stringify(app[key]),
                complete: function (jqXHR) {
                    // capture the text and/or json response
                    let json = jqXHR.responseJSON;

                    // based on the responds code
                    switch (jqXHR.status) {
                        case 200:
                            // 200 in this case is NOT a valid response code
                            app.methods.alert('200 is an invalid response.');
                            break;
                        case 201:
                            // Created
                            app.methods.refreshOnData(json, data);
                            break;
                        case 202:
                            // Accepted
                            app.methods.refreshOnData(json, data);
                            break;
                        case 406:
                            // Not Acceptable
                            app.methods.notAcceptable(json, data);

                            break;
                        default:
                            // anything other reponds code is an error
                            app.methods.alert('Record Access Issue.');
                    }
                }
            })
        },

        // default not accepted form submission
        notAcceptable(json, data) {
            // tag the ui element based on the keys (array) if available 
            if (json.keys) {
                app.gui.highlightErrorFields(json.keys);
            }

            app.gui.showErrorDialog(json);
        },

        refreshOnData(json, data) {
            // capture the data from the html element (this) which the method was triggered
            // hide any modals which might be on screen
            modal.hide();

            // if reload then reload this location (url) data-reload=""
            if (data.reload) {
                location.reload();
            }

            // if refresh then refresh the page data-refresh=""
            if (data.refresh) {
                app.methods.autoLoad();
            }

            // redirect if appropriate
            if (data.redirect) {
                app.methods.redirect(data);
            }
        },

        // load a model into the application property
        model(modelUrl, appProperty, method, thenCall) {
            // make ajax request
            app.methods.makeAjaxCall({
                url: app.methods.makeUrl(modelUrl),
                type: method,
                complete: function (jqXHR) {
                    // capture the text or json from the responds
                    let json = jqXHR.responseJSON;

                    // based on the responds code
                    if (jqXHR.status == 200) {
                        // success
                        // replace the application property with the matching json property
                        if (json != undefined) {
                            app[appProperty] = json[appProperty];

                            if (typeof thenCall === 'function') {
                                thenCall();
                            }
                        } else {
                            app.methods.alert('Could not location property "' + appProperty + '" on JSON response.');
                        }
                    } else {
                        // show error dialog
                        app.methods.alert('Model Access Issue.');
                    }
                }
            });
        },

        // load a layout from the server
        layout(layoutUrl, elementId, method, thenCall) {
            app.methods.makeAjaxCall({
                url: app.methods.makeUrl(layoutUrl),
                type: method,
                complete: function (jqXHR) {
                    if (jqXHR.status == 200) {
                        // success
                        // replace DOM Element with responds json or html
                        app.methods.replaceElement(elementId, jqXHR);

                        if (typeof thenCall === 'function') {
                            thenCall();
                        }
                    } else {
                        // show error dialog
                        app.methods.alert('Layout Access Issue.');
                    }
                }
            });
        },

        // load a layout then load a model
        layoutModel(layoutUrl, elementId, method, modelUrl, modelProperty, modelMethod) {
            // grab a layout and then a model
            app.methods.layout(layoutUrl, elementId, method, function () {
                app.methods.model(modelUrl, modelProperty, modelMethod)
            });
        },

        replaceElement(elementId, jqXHR) {
            // capture the text and json from the responds
            let html = jqXHR.responseText;
            let json = jqXHR.responseJSON;

            // if json.html available use that
            if (json.html !== undefined) {
                // replace the html with json.html
                html = json.html;
            }

            document.getElementById(app.methods.removeSelector(elementId)).innerHTML = html;
        },

        // auto load a models where data-autoload = true
        // using the data attached
        autoLoad() {
            for (let tag of ['preload', 'autoload', 'postload']) {
                $('#' + app.rootElement.id + ' [data-' + tag + '="true"]').each(function () {
                    // grab the other data attributes
                    // grab the url [*required], property [records], method [get]
                    app.methods.model(this.dataset.url, this.dataset.property || 'records', this.dataset.method || 'get');
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
        makeUrl(url, id) {
            return url.replace('-1', id || '');
        },
        removeSelector(string, selector) {
            selector = selector || '#';

            return string.replace(selector, '');
        },
        addSelector(string, selector) {
            selector = selector || '#';

            return selector + string.replace(selector, '');
        },
        // bootbox wrapper
        alert(json) {
            // show the alert
            bootbox.alert(json);
        },
    },
    gui: {
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
        showErrorDialog(json) {
            // this responds is json so we grab the values or use the defaults provided
            // https://bootboxjs.com/documentation.html
            json.size = json.size || 'large'; // large alert
            json.title = json.title || 'Your Form Has The Following Errors'; // default alert title
            json.centerVertical = json.centerVertical || true; // default center vertically
            json.closeButton = json.closeButton || false; // default hide close button

            // format the json errors (array) 
            // here not on the server for display in the next step
            json.message = app.methods.wrap(json.errors, '<i class="fa-solid fa-triangle-exclamation"></i> ', '</br>');

            app.methods.alert(json);
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

