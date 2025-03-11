class App {
    id = undefined;
    appElement = undefined;
    model = undefined;
    // app internal storage
    storage = {};

    constructor(id, model) {
        // root application DOM id
        this.id = id;
        this.appElement = document.getElementById(this.id);
        // save a copy of the model we are working with in this App instance
        this.model = model;
        // add me to the global scope for easy access
        window['@app'] = this;

        // if they include a start function [function]
        if (model.start) {
            model.start(this);
        }

        this.rebind();
    };

    rebind() {
        tinybind.bind(this.appElement, this.model);
    };

    // auto detect
    go(args) {
        console.log(args);
        if (args.method) {
            this.submit(args);
        } else if (args.url) {
            this.redirect(args);
        } else {
            this.swap(args);
        }
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
            this.loadModel(args.model, args.property, args.node, args.options);
        }
        // process 1 or more selectors comma separated string [string] 
        if (args.refresh) {
            this.setTo(args.refresh, '//refresh//');
        }
        // show 1 or more elements comma separated string [string] 
        if (args.show) {
            this.setTo(args.show, true);
        }
        // call this model function usually something like actions.doSomethingCool
        if (args.action) {
            this.callModelAction(args.action, args);
        }
    };

    redirect(args) {
        // redirect to another url
        window.location.href = args.url;
    };

    submit(args) {
        let parent = this;
        // either get the property specified (dot notation) or the entire model
        let payload = (args.property) ? this.getProperty(undefined, args.property) : this.model;

        this.makeAjaxCall({
            // get the url to post to with # replacement from the objects uid
            url: args.url,
            // what http method should we use
            type: args.method ?? 'POST',
            // what should we send as "data"
            data: JSON.stringify(payload),
            // when the request is "complete"
            complete: function (jqXHR) {
                // save these so we can pass them though
                args.jqXHR = jqXHR;
                args.json = jqXHR.responseJSON;

                // based on the responds code
                switch (jqXHR.status) {
                    // 200 in this case is NOT a valid response code
                    // Created 201
                    // Accepted 202
                    case 201:
                    case 202:
                        parent.onSuccess(args);
                        break;
                    case 406:
                        parent.onFailure(args);
                        break;
                    default:
                        // anything other reponds code is an error
                        parent.alert('Record Access Issue (' + jqXHR.status + ').');
                }
            }
        })
    };

    onSuccess(args) {
        if (args['on-created-action']) {
            this.callModelAction(args['on-created-action'], args);
        }
        if (args['on-accepted-action']) {
            this.callModelAction(args['on-accepted-action'], args);
        }
        // call hide in these DOM elements
        if (args['on-success-hide']) {
            this.hide(args['on-success-hide']);
        }
        // process these DOM elements
        if (args['on-success-refresh']) {
            this.setTo(args['on-success-refresh'], '//refresh//');
        }
        if (args['on-success-true']) {
            this.setTo(args['on-success-true'], true);
        }
        if (args['on-success-false']) {
            this.setTo(args['on-success-false'], false);
        }
        if (args['on-success-toggle']) {
            this.setTo(args['on-success-toggle'], '//toggle//');
        }
        // call show in these DOM elements
        if (args['on-success-show']) {
            this.show(args['on-success-show']);
        }
        // call this model function usually something like actions.doSomethingCool
        if (args['on-success-action']) {
            this.callModelAction(args['on-success-action'], args);
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

    onFailure(args) {
        if (args['on-failure-property']) {
            this.setProperty(undefined, args['on-failure-property'], args.json);
        }
        if (args['on-failure-action']) {
            this.callModelAction(args['on-failure-action'], args);
        }
        if (args['on-failure-merge']) {
            this.mergeModels(undefined, args.json);
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
                parent.loadModel(args.model, args.property, args.node, args.options);
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
                let current = parent.getProperty(undefined, dotnotation);
                value = !current;
            }
            if (value == '//refresh//') {
                value = new Date();
            }

            parent.setProperty(undefined, dotnotation, value);
        });
    };

    callModelAction(modelMethodName, args) {
        this.getProperty(undefined, modelMethodName)(this, args);
    };

    // bootbox wrapper
    alert(record) {
        // show the alert
        bootbox.alert(record);
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
                            parent.setProperty(undefined, appProperty, jsonObject);
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
        let current = obj ?? this.model;
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
        let value = obj ?? this.model;
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
        let skip = ['construct', 'actions'];
        currentModel = currentModel ?? this.model;

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