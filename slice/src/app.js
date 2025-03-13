/*
action [method call]
property [insert responds into this model property] (must have args.json)
method + url [ajax send]
model[ajax get]
refresh [refresh a property]
toggle [invert boolean]
hide [set to false]
false [set to false]
show [set to true]
true [set to true]
redirect [redirect to url]
reload [reload this page]
then [method call]
rebind (force rebind) (value doesn't matter)

on-success-*
on-failure-*

# 200's
on-ok-action [method call]
on-created-action [method call]
on-accepted-action [method call]

# 400's
on-not-acceptable-action [method call]

all method calls are on the model and you must specify the action.
*/

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
     * redirect to a supplied url
     * 
     * @param {object} args 
     */
    redirect(args) {
        // redirect to another url
        window.location.href = args.url;
    };

    /**
     * auto detect what you are trying to do
     * based on the arguments
     * 
     * @param {object} args 
     */
    go(args) {
        // do we need to setup the redirect url?
        // if we have url and NOT method then use it as the redirect
        args.redirect = (args.url && !args.method) ? args.url : undefined;

        this.onAttrs(undefined, args);
    };

    /**
     * handlers for the success & failure tags
     * 
     * @param {string} txt 
     * @param {object} args 
     */
    onAttrs(txt, args) {
        txt = (txt) ? txt = 'on-' + txt + '-' : '';

        console.log(txt, args);

        if (args[txt + 'action']) {
            this.callModelActions(args[txt + 'action'], args);
        }

        if (args[txt + 'property'] && args.json) {
            if (args[txt + 'node']) {
                args.json = parent.getProperty(args.json, args[txt + 'node']);
            }

            if (args[txt + 'property'] == '@root') {
                this.mergeModels(undefined, args.json);
            } else {
                this.setProperty(undefined, args[txt + 'property'], args.json);
            }
        }

        if (args[txt + 'method'] && args[txt + 'url']) {
            // send(url, method, property, args)
            this.send(args[txt + 'url'], args[txt + 'method'], args[txt + 'property'], args);
        }

        if (args[txt + 'model']) {
            // send(url, method, property, args)
            this.send(args[txt + 'model'], args[txt + 'method'] ?? 'GET', args[txt + 'property'], args);
        }

        if (args[txt + 'refresh']) {
            this.setProperties(undefined, args[txt + 'refresh'], new Date());
        }

        if (args[txt + 'toggle']) {
            this.setProperties(undefined, args[txt + 'toggle'], !this.getProperty(undefined, args[txt + 'toggle']));
        }

        if (args[txt + 'hide']) {
            this.setProperties(undefined, args[txt + 'hide'], false);
        }

        if (args[txt + 'false']) {
            this.setProperties(undefined, args[txt + 'false'], false);
        }

        if (args[txt + 'show']) {
            this.setProperties(undefined, args[txt + 'show'], true);
        }

        if (args[txt + 'true']) {
            this.setProperties(undefined, args[txt + 'true'], true);
        }

        if (args[txt + 'then']) {
            this.callModelActions(args[txt + 'then'], args);
        }

        if (args[txt + 'rebind']) {
            this.rebind();
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
     * send a ajax request
     * 
     * @param {string} url 
     * @param {string} method 
     * @param {string} property 
     * @param {object} args 
     */
    send(url, method, property, args) {
        let parent = this;

        // either get the property specified (dot notation) or the entire model
        let payload = (property) ? this.getProperty(undefined, property) : this.model;

        $.ajax({
            dataType: 'json',
            contentType: 'application/json; charset=utf-8',
            // get the url to post to with # replacement from the objects uid
            url: url,
            // what http method should we use
            type: method ?? 'POST',
            // what should we send as "data"
            data: JSON.stringify(payload),
            // when the request is "complete"
            complete: function (jqXHR) {
                // save these so we can pass them though
                args.jqXHR = jqXHR;
                args.json = jqXHR.responseJSON;

                // based on the responds code
                switch (jqXHR.status) {
                    case 200:
                        if (args['on-ok-action']) {
                            parent.callModelActions(args['on-ok-action'], args);
                        }
                        parent.onAttrs('success', args);
                        break;
                    case 201:
                        // 201 Created
                        if (args['on-created-action']) {
                            parent.callModelActions(args['on-created-action'], args);
                        }
                        parent.onAttrs('success', args);
                        break;
                    case 202:
                        // 202 Accepted
                        if (args['on-accepted-action']) {
                            parent.callModelActions(args['on-accepted-action'], args);
                        }
                        parent.onAttrs('success', args);
                        break;
                    case 406:
                        // 406 Not Acceptable
                        if (args['on-not-acceptable-action']) {
                            parent.callModelActions(args['on-not-acceptable-action'], args);
                        }
                        parent.onAttrs('failure', args);
                        break;
                    default:
                        // anything other reponds code is an error
                        parent.alert('Unknown Status: ' + jqXHR.status);
                }
            }
        });
    };

    updateModels(selectors) {
        for (let selector of this.split(selectors)) {
            this.updateModel(selector);
        };
    };

    updateModel(element) {
        if (typeof element === 'string' || element instanceof String) {
            element = document.getElementById(element);
        }

        if (element) {
            let args = this.getAttr(element);

            if (args.model) {
                // send(url, method, property, args)
                this.send(args.model, args.method ?? 'GET', args.property, args);
            }
        } else {
            console.error('Not an DOM element:', element);
        }
    };

    /**
     * update 1 or more model method names separated by ,
     * 
     * @param {string,array} modelMethodNames 
     * @param {object} args 
     */
    callModelActions(modelMethodNames, args) {
        for (let modelMethodName of this.split(modelMethodNames)) {
            this.callModelAction(modelMethodName, args);
        };
    }

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
     * update 1 or more properties separated by ,
     * 
     * @param {object} obj 
     * @param {array|string} properties 
     * @param {mixed} value 
     */
    setProperties(obj, properties, value) {
        for (let property of this.split(properties)) {
            this.setProperty(obj, property, value);
        };
    }

    /**
     * set a property on the model using dot notation
     * 
     * @param {object} obj [this.model]
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
     * @param {object} obj [this.model]
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
     * single level model merge
     * 
     * @param {object} currentModel [this.model]
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
     * split string into an array
     * 
     * @param {string|array} arg 
     * @returns array
     */
    split(arg) {
        return (!Array.isArray(arg)) ? arg.split(',') : arg;
    }

    /**
     * bootbox wrrapper
     * 
     * @param {object} args 
     */
    alert(args) {
        bootbox.alert(args);
    };
}