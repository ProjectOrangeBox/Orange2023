class App {
    // root application DOM element
    rootElement = undefined;
    loader = undefined;
    models = undefined;
    modal = undefined;
    gui = undefined;

    constructor(id, models) {
        this.rootElement = document.getElementById(id);
        this.loader = new loader(this);
        this.gui = new gui(this);
        this.models = models;
        this.models.app = this;
        this.modal = new Modal(this);

        tinybind.bind(this.rootElement, this.models);

        this.autoLoad();
    };

    autoLoad() {
        let parent = this;

        for (let tag of ['preload', 'autoload', 'postload']) {
            let elements = document.querySelectorAll('[' + tag + ']')
            elements.forEach(function (element, key, list) {
                let args = getAttr(list[key]);
                console.log(args);
                if (args.template && args.model) {
                    parent.loader.template(args, parent.loader.model(args));
                } else if (args.template) {
                    // templateUrl, elementId, options, thenCall
                    parent.loader.template(parent.makeUrl(args.template, args), args.element, undefined);
                } else if (args.model) {
                    // modelUrl, appProperty, modelProperty, options, thenCall
                    parent.loader.model(parent.makeUrl(args.model, args), args.property, undefined);
                }
            });
        }
    };

    redirect(args) {
        let url = this.makeUrl(args.url, { uid: args.uid || -1, ...args });

        window.location.href = url;
    };

    submit(args) {
        let parent = this;

        // get the payload for the http call from app based on the property tag
        let url = this.makeUrl(args.url, { uid: args.id || -1, ...args });

        this.makeAjaxCall(this, {
            // get the url to post to with # replacement from the objects uid
            url: url,
            // what http method should we use
            type: args.httpMethod || 'post',
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
        let uid = args.uid || '';
        let segs = window.location.href.split('/');
        segs.shift(); // http(s)
        segs.shift(); // /

        // the default
        url = url.replace("{uid}", uid);

        for (let index = 0; index < segs.length; index++) {
            url = url.replace("{seg" + index + "}", segs[index]);
        }

        const attributeMap = args;

        for (let i = 0; i < attributeMap.length; i++) {
            const attribute = attributeMap[i];
            url = url.replace('{' + attribute.name + '}', attribute.value);
        }

        return url;
    };

    // bootbox wrapper
    alert(record) {
        // show the alert
        bootbox.alert(record);
    };

    openModal(name, config) {
        if (this.modal[name] === undefined) {
            this.modal[name] = new Modal(name, config);
        }

        return app.modal[name];
    };

    closeModal(name) {
        this.modal[name].hide();
    };

    makeAjaxCall(parent, request) {
        console.log(request);

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