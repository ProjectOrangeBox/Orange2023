class App {
    // DOM id to attach to
    id = undefined;
    // DOM id element
    appElement = undefined;
    // model we are using
    model = undefined;
    // app internal storage
    storage = {};

    /**
     * setup the App class
     * 
     * @param {string} id 
     * @param {object} model 
     */
    constructor(id, model) {
        // root application DOM id
        this.id = id;
        // DOM id element
        this.appElement = document.getElementById(this.id);
        // save a copy of the model we are working with in this App instance
        this.model = model;
        // add me to the global scope for easy access
        window['@tinybind'] = this;

        // if they include a start function [function]
        if (model.start) {
            model.start(this);
        }

        this.rebind();
    };

    /**
     * bind / rebind the element and model
     */
    rebind() {
        tinybind.bind(this.appElement, this.model);
    };

    /**
     * auto detect what you are trying to do
     * based on the arguments
     * 
     * @param {object} args 
     */
    go(args) {
        if (args.method) {
            this.submit(args);
        } else if (args.url) {
            this.redirect(args);
        } else {
            this.on(args);
        }
    };

    /**
     * basic handlers
     * 
     * @param {object} args 
     */
    on(args) {
        this.onBlank(undefined, args);
    };

    /**
     * redirect to a supplied url
     * 
     * @param {object} args 
     */
    redirect(args) {
        // redirect to another url
        window.location.href = args.url;
    };

    /**
     * send a ajax request
     * 
     * @param {object} args
     */
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
                    case 201:
                        // 201 Created
                        if (args['on-created-action']) {
                            parent.callModelAction(args['on-created-action'], args);
                        }
                        parent.onBlank('success', args);
                    case 202:
                        // 202 Accepted
                        if (args['on-accepted-action']) {
                            parent.callModelAction(args['on-accepted-action'], args);
                        }
                        parent.onBlank('success', args);
                        break;
                    case 406:
                        // 406 Not Acceptable
                        if (args['on-not-acceptable-action']) {
                            parent.callModelAction(args['on-not-acceptable-action'], args);
                        }
                        parent.onBlank('failure', args);
                        break;
                    default:
                        // anything other reponds code is an error
                        parent.alert('Record Access Issue (' + jqXHR.status + ').');
                }
            }
        })
    };

    /**
     * handlers for the success & failure tags
     * 
     * @param {string} txt 
     * @param {object} args 
     */
    onBlank(txt, args) {
        txt = (txt) ? txt = 'on-' + txt + '-' : '';

        if (args[txt + 'action']) {
            this.callModelAction(args[txt + 'action'], args);
        }
        if (args[txt + 'property']) {
            if (args[txt + 'property'] == '@root') {
                this.mergeModels(undefined, args.json);
            } else {
                this.setProperty(undefined, args[txt + 'property'], args.json);
            }
        }
        // make a model ajax request
        if (args['model']) {
            // model(modelUrl, appProperty, modelProperty, options, thenCall)
            this.loadModel(args['model'], args.property, args.node, args.options);
        }
        // call hide in these DOM elements
        if (args[txt + 'hide']) {
            this.setTo(args[txt + 'hide'], false);
        }
        // process these DOM elements
        if (args[txt + 'refresh']) {
            this.setTo(args[txt + 'refresh'], new Date());
        }
        if (args[txt + 'true']) {
            this.setTo(args[txt + 'true'], true);
        }
        if (args[txt + 'false']) {
            this.setTo(args[txt + 'false'], false);
        }
        if (args[txt + 'toggle']) {
            this.setTo(args[txt + 'toggle'], !this.getProperty(undefined, args[txt + 'toggle']));
        }
        // call show in these DOM elements
        if (args[txt + 'show']) {
            this.setTo(args[txt + 'show'], true);
        }
        // redirects to NEW URL full context switch
        if (args[txt + 'redirect']) {
            window.location.href = args[txt + 'redirect'];
        }
        // reloads the ENTIRE URL full context switch
        if (args[txt + 'reload']) {
            location.reload();
        }
    };

    /**
     * update 1 or more models
     * 1 or more dom (html) ids 
     * 
     * @param {string} selectors 
     */
    updateModel(selectors) {
        // for each selector
        for (let selector of this.split(selectors)) {
            this.updateModelElement(document.getElementById(selector));
        };
    };

    /**
     * update a individual model element
     * based on it's attributes
     * 
     * @param {dom element} element 
     */
    updateModelElement(element) {
        if (element) {
            let args = this.getAttr(element);

            if (args.model) {
                // modelUrl, appProperty, modelProperty, options, thenCall
                this.loadModel(args.model, args.property, args.node, args.options);
            }
        } else {
            console.error('Not an DOM element:', element);
        }
    };

    /**
     * set 1 or more properties using dot notation
     * separated by commas
     * to a value
     * when supplying multiple dot notation they all get the same value
     * 
     * @param {string} dotnotations 
     * @param {mixed} value 
     */
    setTo(dotnotations, value) {
        for (let dotnotation of this.split(dotnotations)) {
            this.setProperty(undefined, dotnotation, value);
        };
    };

    /**
     * call a method on the model
     * 
     * @param {string} modelMethodName 
     * @param {object} args 
     */
    callModelAction(modelMethodName, args) {
        this.getProperty(undefined, modelMethodName)(this, args);
    };

    /**
     * bootbox wrrapper
     * 
     * @param {object} args 
     */
    alert(args) {
        bootbox.alert(args);
    };

    /**
     * make the actual ajax call wrapper
     * 
     * @param {object} request 
     */
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

    /**
     * make a ajax call to load a model
     * 
     * @param {string} modelUrl 
     * @param {string} appProperty 
     * @param {string} modelProperty 
     * @param {object} options 
     * @param {function} thenCall 
     */
    loadModel(modelUrl, appProperty, modelProperty, options, thenCall) {
        options = options ?? {};

        let parent = this;

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

    /**
     * set a property on the model using dot notation
     * 
     * @param {object} obj 
     * @param {string} dotnotation 
     * @param {mixed} value 
     */
    setProperty(obj, dotnotation, value) {
        let properties = dotnotation.split('.');
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

    /**
     * get a property off the model using dot notation
     * 
     * @param {object} obj 
     * @param {string} dotnotation 
     * @returns mixed
     */
    getProperty(obj, dotnotation) {
        let properties = dotnotation.split('.');
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

    /**
     * global capture all attributes on a element
     * 
     * @param {dom element} element 
     * @returns object
     */
    getAttr(element) {
        let args = {};

        for (let attr of element.attributes) {
            args[attr.name] = attr.value;
        }

        return args;
    };

    /**
     * Only split if it's not already an array
     * 
     * @param {array|string} input 
     * @param {string} on 
     * @returns array
     */
    split(input, on) {
        on = on ?? ','

        if (!Array.isArray(input)) {
            input = input.split(on);
        }

        return input;
    };

    /**
     * single level model merge
     * 
     * @param {object} currentModel 
     * @param {object} replacementModel 
     */
    mergeModels(currentModel, replacementModel) {
        currentModel = currentModel ?? this.model;

        for (const [key, value] of Object.entries(replacementModel)) {
            // our default "methods" you can't replace
            if (!['construct', 'actions'].includes(key)) {
                currentModel[key] = value;
            }
        }
    };

    /**
     * let object = dotToObject('person.name','Joe');
     * 
     * @param {string} dotNotationString 
     * @param {mixed} value 
     * @returns object
     */
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