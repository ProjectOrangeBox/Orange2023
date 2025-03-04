class App {
    // root application DOM element
    id = undefined;
    rootElement = undefined;
    loader = undefined;
    models = undefined;
    modals = undefined;
    gui = undefined;

    constructor(id, models) {
        this.id = id;
        this.rootElement = document.getElementById(id);
        this.loader = new Loader(this);
        this.gui = new Gui(this);
        this.models = models;
        this.models.app = this;
        this.modals = {};
        this.autoLoad();
    };

    rebind() {
        tinybind.bind(this.rootElement, this.models);
    }

    autoLoad(selector) {
        var parent = this;

        selector = selector || this.id;

        for (let tag of ['preload', 'autoload', 'postload']) {
            document.querySelectorAll('#' + selector + ' [' + tag + ']').forEach(function (element, key, list) {
                let args = getAttr(list[key]);

                if (args.template && args.model) {
                    parent.loader.template(parent.makeUrl(args.template, args), args.element, args.options, parent.loader.model(args));
                } else if (args.template) {
                    // templateUrl, elementId, options, thenCall
                    parent.loader.template(parent.makeUrl(args.template, args), args.element, args.options);
                } else if (args.model) {
                    // modelUrl, appProperty, modelProperty, options, thenCall
                    parent.loader.model(parent.makeUrl(args.model, args), args.property, args.node, args.options);
                } else if (args.modal) {
                    args.element = element;
                    args.templateUrl = parent.makeUrl(args.modal, args);
                    args.options = JSON.parse(args.options || '{}');
                    parent.addModal(args.name, args);
                }
            });
        }
    };

    // load modal template
    loadModal(args) {
        var parent = this;

        // modelUrl, appProperty, modelProperty, options, thenCall
        this.loader.model(this.makeUrl(args.model, args), args.property, args.node, {}, function () {
            parent.openModal(args.name);
        });
    };

    redirect(args) {
        window.location.href = this.makeUrl(args.url, args);
    };

    submit(args) {
        var parent = this;
        var data = JSON.stringify(getProperty(this.models, args.property || 'record'));

        this.makeAjaxCall({
            // get the url to post to with # replacement from the objects uid
            url: this.makeUrl(args.url, args),
            // what http method should we use
            type: args.method || 'post',
            // what should we send as "data"
            data: data,
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
                        parent.alert('200 is an invalid response.');
                        break;
                    case 201:
                        // Created
                        parent.actionBasedOnArguments(args);
                        break;
                    case 202:
                        // Accepted
                        parent.actionBasedOnArguments(args);
                        break;
                    case 406:
                        // Not Acceptable
                        parent.notAcceptable(args);
                        break;
                    default:
                        // anything other reponds code is an error
                        parent.alert('Record Access Issue.');
                }
            }
        })
    };

    // default not accepted form submission
    notAcceptable(args) {
        // tag the ui element based on the keys (array) if available 
        if (args.json.keys) {
            // add the highlights if we can
            this.gui.highlightErrorFields(args.json.keys);
        }
        // show error dialog
        this.gui.showErrorDialog(args);
    };

    makeUrl(url, args) {
        // url segments /foo/bar/{$3}
        let segs = window.location.href.split('/');

        segs.shift(); // http(s)
        segs.shift(); // /

        for (let index = 0; index < segs.length; index++) {
            url = url.replace('{' + index + '}', segs[index]);
        }

        for (let property in args) {
            if (property.substring(0, 8) == 'replace-') {
                url = url.replace("{" + property.substring(8) + "}", args[property]);
            }
        }

        return url;
    };

    // bootbox wrapper
    alert(record) {
        // show the alert
        bootbox.alert(record);
    };

    addModal(name, args) {
        if (this.modals[name] === undefined) {
            args.app = this;

            this.modals[name] = new Modal(name, args);

            this.autoLoad(this.modals[name].id);

            //console.log(name, args);
        }

        return this.modals[name];
    }

    openModal(name) {
        this.modals[name].show();
    };

    closeModal(name) {
        this.modals[name].hide();
    };

    makeAjaxCall(request) {
        // the ajax call defaults
        let defaults = {
            // The type of data that you're expecting back from the server.
            dataType: 'json',
            // When sending data to the server, use this content type.
            contentType: 'application/json; charset=utf-8',
            // Request Method
            type: 'get',
        };

        // merge down the defaults
        $.ajax({ ...defaults, ...request });
    };

    actionBasedOnArguments(args) {
        if (args['on-success-close-modal'] || false) {
            this.closeModal(args['on-success-close-modal']);
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
            this.autoLoad();
        }

        // redirect if appropriate
        if (args.redirect || false) {
            window.location.href = args.redirect;
        }
    };

}