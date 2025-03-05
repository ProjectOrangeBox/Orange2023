class App {
    // root application DOM element
    id = undefined;
    models = undefined;
    gui = undefined;
    // app internal storage
    storage = {};

    constructor(id, models) {
        this.id = id;
        this.gui = new Gui(this);
        this.models = models;
        this.models.app = this;
        this.autoLoad(this.id);
    };

    autoLoad(selector) {
        var parent = this;

        selector = selector ?? this.id;

        selector.split(',').forEach(function (value) {
            for (let tag of [' [preload]', ' [autoload]', ' [postload]', '[preload]', '[autoload]', '[postload]']) {
                document.querySelectorAll('#' + value + tag).forEach(function (element) {
                    let args = parent.getAttr(element);

                    if (args.model) {
                        // modelUrl, appProperty, modelProperty, options, thenCall
                        parent.model(parent.makeUrl(args.model, args), args.property, args.node, args.options);
                    }
                });
            }
        });
    };

    rebind() {
        tinybind.bind(document.getElementById(this.id), this.models);
    };

    swap(args) {
        if (args.hide) {
            this.hide(args.hide);
        }
        if (args.model) {
            // model(modelUrl, appProperty, modelProperty, options, thenCall)
            this.model(this.makeUrl(args.model, args), args.property, args.node, args.options);
        }
        if (args.refresh) {
            this.autoLoad(args.refresh);
        }
        if (args.show) {
            this.show(args.show);
        }
    };

    show(id) {
        var parent = this;

        id.split(',').forEach(function (value) {
            const el = document.querySelector('#' + value);

            // is this a modal or form?
            if (el.classList.contains('modal')) {
                if (!parent.storage['modal-' + value]) {
                    parent.storage['modal-' + value] = new bootstrap.Modal('#' + value);
                }

                parent.storage['modal-' + value].show();
            } else {
                el.classList.remove('d-none');
            }
        });
    };

    hide(id) {
        var parent = this;

        id.split(',').forEach(function (value) {
            const el = document.querySelector('#' + value);

            if (el.classList.contains('modal')) {
                parent.storage['modal-' + value].hide();
            } else {
                el.classList.add('d-none');
            }
        });
    };

    redirect(args) {
        window.location.href = this.makeUrl(args.url, args);
    };

    submit(args) {
        var parent = this;

        var data = JSON.stringify(this.getProperty(this.models, args.property ?? 'record'));

        this.makeAjaxCall({
            // get the url to post to with # replacement from the objects uid
            url: this.makeUrl(args.url, args),
            // what http method should we use
            type: args.method ?? 'post',
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
                        parent.onSuccess(args);
                        break;
                    case 202:
                        // Accepted
                        parent.onSuccess(args);
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

    onSuccess(args) {
        this.gui.removeIsInvalid(this.id);

        if (args['on-success-hide']) {
            this.hide(args['on-success-hide']);
        }

        if (args['on-success-show']) {
            this.show(args['on-success-show']);
        }

        if (args['on-success-refresh']) {
            let id = (args['on-success-refresh'] == 'true') ? this.id : args['on-success-refresh'];

            this.autoLoad(id);
        }

        if (args['on-success-redirect']) {
            // redirects to NEW URL full context switch
            window.location.href = args['on-success-redirect'];
        }

        if (args['on-success-reload']) {
            // reloads the ENTIRE URL full context switch
            location.reload();
        }
    };

    // default not accepted form submission
    notAcceptable(args) {
        this.gui.notAcceptable(args);
    };

    // bootbox wrapper
    alert(record) {
        // show the alert
        bootbox.alert(record);
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

    model(modelUrl, appProperty, modelProperty, options, thenCall) {
        options = options ?? {};

        var parent = this;

        this.makeAjaxCall({
            url: modelUrl,
            type: options.method ?? 'get',
            complete: function (jqXHR) {
                // based on the responds code
                if (jqXHR.status == 200) {
                    // success

                    // capture the text or json from the responds
                    let jsonObject = jqXHR.responseJSON;

                    // replace the application property with the matching json property
                    if (jsonObject) {
                        let record = modelProperty ? parent.getProperty(jsonObject, modelProperty) : jsonObject;

                        parent.setProperty(parent.models, appProperty, record);

                        parent.rebind();

                        if (typeof thenCall === 'function') {
                            thenCall(arguments);
                        }
                    } else {
                        parent.alert('Could not load model.');
                    }
                } else {
                    // show error dialog
                    parent.alert('Model returned the status [' + jqXHR.status + '].');
                }
            }
        });
    };

    setProperty(obj, path, value) {
        let properties = path.split('.');
        let current = obj;
        for (let i = 0; i < properties.length - 1; i++) {
            let prop = properties[i];
            if (current[prop] === undefined || current[prop] === null) {
                current[prop] = {};
            }
            current = current[prop];
        }

        current[properties[properties.length - 1]] = value;
    };

    getProperty(obj, path) {
        let properties = path.split('.');
        let value = obj;
        for (let prop of properties) {
            if (value && typeof value === 'object' && value.hasOwnProperty(prop)) {
                value = value[prop];
            } else {
                return undefined;
            }
        }
        return value;
    };

    // global capture all attributes on a element
    getAttr(element) {
        let args = {};

        for (let attr of element.attributes) {
            args[attr.name] = attr.value;
        }

        return args;
    };
}