class App {
    id = undefined;
    appElement = undefined;
    model = undefined;
    gui = undefined;
    // app internal storage
    storage = {};

    constructor(id, model) {
        // root application DOM id
        this.id = id;
        this.appElement = document.getElementById(this.id);
        // save a reference to the gui object
        // this should have all of the DOM interaction / css framework code so it is easy to replace
        this.gui = new Gui(this);
        // save a copy of the model we are working with in this App instance
        this.model = model;

        window['@app'] = this;

        // if they include any preload DOM ids [array]
        /*
        if (model.preload) {
            this.updateModel(model.preload);
        }
        */

        // if they include a start function [function]
        if (model.start) {
            model.start(this);
        }

        tinybind.bind(this.appElement, this.model);
    };

    rebind() {
        tinybind.bind(this.appElement, this.model);
    };

    // swap html "element"
    swap(args) {
        // hide 1 or more elements comma separated string [string] 
        if (args.hide) {
            this.setTo(args.hide, false);
        }
        // make a model ajax request
        if (args.model) {
            // model(modelUrl, appProperty, modelProperty, options, thenCall)
            this.loadModel(this.makeUrl(args.model, args), args.property, args.node, args.options);
        }
        // process 1 or more selectors comma separated string [string] 
        if (args.refresh) {
            this.setTo(args.refresh, Date.now());
        }
        // show 1 or more elements comma separated string [string] 
        if (args.show) {
            this.setTo(args.show, true);
        }
        // call this model function usually something like actions.doSomethingCool
        if (args.then) {
            this.callModelAction(args.then);
        }
    };

    redirect(args) {
        // redirect to another url
        window.location.href = this.makeUrl(args.url, args);
    };

    submit(args) {
        let parent = this;
        // either get the property specified (dot notation) or the entire model
        let payload = (args.property) ? this.getProperty(this.model, args.property) : this.model;

        this.makeAjaxCall({
            // get the url to post to with # replacement from the objects uid
            url: this.makeUrl(args.url, args),
            // what http method should we use
            type: args.method ?? 'post',
            // what should we send as "data"
            data: JSON.stringify(payload),
            // when the request is "complete"
            complete: function (jqXHR) {
                // capture the text and/or json response
                let json = jqXHR.responseJSON;
                // save these so we can pass them though
                args.jqXHR = jqXHR;
                args.json = json;

                // based on the responds code
                switch (jqXHR.status) {
                    // 200 in this case is NOT a valid response code
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
                        parent.alert('Record Access Issue (' + jqXHR.status + ').');
                }

                // call this model function usually something like actions.doSomethingCool
                if (args.then) {
                    this.callModelAction(args.then);
                }
            }
        })
    };

    onSuccess(args) {
        // call hide in these DOM elements
        if (args['on-success-hide']) {
            this.hide(args['on-success-hide']);
        }
        // process these DOM elements
        if (args['on-success-refresh']) {
            this.setTo(args['on-success-refresh'], Date.now());
        }
        if (args['on-success-true']) {
            this.setTo(args['on-success-true'], true);
        }
        if (args['on-success-false']) {
            this.setTo(args['on-success-false'], false);
        }
        if (args['on-success-toggle']) {
            this.setTo(args[''], '//toggle//');
        }
        // call show in these DOM elements
        if (args['on-success-show']) {
            this.show(args['on-success-show']);
        }
        // call this model function usually something like actions.doSomethingCool
        if (args['on-success-then']) {
            this.callModelAction(args['on-success-then']);
        }
        // redirects to NEW URL full context switch
        if (args['on-success-redirect']) {
            window.location.href = args['on-success-redirect'];
        }
        // reloads the ENTIRE URL full context switch
        if (args['on-success-reload']) {
            location.reload();
        }
    };

    updateModel(selectors) {
        let parent = this;

        // for each selector
        this.split(selectors).forEach(function (selector) {
            // process the attributes on the DOM element
            parent.updateModelElement(document.getElementById(selector));
        });
    };

    updateModelElement(element) {
        let parent = this;

        if (element) {
            let args = parent.getAttr(element);

            if (args.model) {
                // modelUrl, appProperty, modelProperty, options, thenCall
                parent.loadModel(parent.makeUrl(args.model, args), args.property, args.node, args.options);
            }
        } else {
            console.error('Not an DOM element:', element);
        }
    };

    // handle show and hide automatically even if it is a modal
    show(dotnotations) {
        this.setTo(dotnotations, true);
    };

    hide(dotnotations) {
        this.setTo(dotnotations, false);
    };

    setTo(dotnotations, value) {
        var parent = this;

        this.split(dotnotations).forEach(function (dotnotation) {
            if (value == '//toggle//') {
                let current = parent.getProperty(parent.model, dotnotation);
                value = !current;
            }

            parent.setProperty(parent.model, dotnotation, value);
        });
    };

    callModelAction(modelMethodName) {
        this.getProperty(this.model, modelMethodName)(this);
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

    loadModel(modelUrl, appProperty, modelProperty, options, thenCall) {
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
                        if (modelProperty) {
                            jsonObject = parent.getProperty(jsonObject, modelProperty);
                        }

                        if (appProperty) {
                            // if they only want to replace a single property on the model
                            parent.setProperty(parent.model, appProperty, jsonObject);
                        } else {
                            // if they want to merge the current model with the object received
                            parent.mergeModels(parent.model, jsonObject);
                        }

                        // force update the DOM
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

    split(input, on) {
        on = on ?? ','

        if (!Array.isArray(input)) {
            input = input.split(on);
        }

        return input;
    };

    mergeModels(currentModel, replacementModel) {
        // our default methods you can't replace
        let skip = ['actions', 'preload', 'start'];

        for (const [key, value] of Object.entries(replacementModel)) {
            if (!skip.includes(key)) {
                currentModel[key] = value;
            }
        }
    };

    dotToObject(dotNotationString, value) {
        const parts = dotNotationString.split('.');

        let obj = {};
        let current = obj;

        for (let i = 0; i < parts.length - 1; i++) {
            const part = parts[i];
            current[part] = {};
            current = current[part];
        }

        current[parts[parts.length - 1]] = value;

        return obj;
    };

}