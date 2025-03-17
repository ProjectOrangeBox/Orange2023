/*
action [method on model call]
property [incoming or outgoing model property] (supports @root)
method [ajax send] (defaults to GET)
model [ajax get] (url of end point)
refresh [refresh a property]
toggle [invert boolean]
hide [set to false]
false [set to false]
show [set to true]
true [set to true]
redirect [redirect to url]
reload [reload this page]
then [method on model call]
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
     * Takes an id (DOM element ID) and a model (data object).
     * Stores references to:
     * The root element (appElement).
     * The model.
     * Exposes the instance globally (window['@tinybind'] = this).
     * Calls a start function if it exists in the model.
     * Binds the model to the DOM using tinybind.
     * 
     * @param {string} id 
     * @param {object} model 
     */
    constructor(id, model) {
        // root application DOM id
        this.id = id;
        // DOM id element
        this.appElement = document.getElementById(id);
        // save a copy of the model we are working with in this App instance
        this.model = model;
        // app internal storage
        this.storage = {};
        // add me to the global scope for easy access
        window['@tinybind'] = this;

        // if they include a start function [function]
        model?.start?.(this);

        this.rebind();
    }

    /**
     * Uses tinybind.bind(this.appElement, this.model) to update the DOM when the model changes.
     */
    rebind() {
        if (this.appElement) tinybind.bind(this.appElement, this.model);
    }

    /* Navigation (redirect and go) */

    /**
     * redirect(args): Redirects the page using window.location.href = args.url.
     * 
     * 
     * @param {object} args 
     */
    redirect(url) {
        if (url) window.location.href = url;
    }

    /**
     * go(args): Determines if a redirection is needed or another method should be executed.
     * 
     * @param {object} args 
     */
    go(args) {
        this.onAttrs(undefined, args);
    }

    /**
     * Event Handling (onAttrs)
     * Handles different actions based on event attributes (e.g., on-success-action).
     * Triggers model actions, AJAX requests, UI updates, or navigation based on arguments.
     * 
     * @param {string} txt 
     * @param {object} args 
     */
    onAttrs(txt, args) {
        txt = (txt) ? txt = 'on-' + txt + '-' : '';

        if (args[txt + 'action']) {
            this.callModelActions(args[txt + 'action'], args);
        }

        if (args[txt + 'node']) {
            args.json = this.getProperty(args.json, args[txt + 'node']);
        }

        // ONLY if json is INCOMING set */
        if (args.json && args[txt + 'property']) {
            this.setProperty(undefined, args[txt + 'property'], args.json);
        }

        if (args[txt + 'model']) {
            if (args[txt + 'property']) {
                args.jsonText = JSON.stringify(this.getProperty(undefined, args[txt + 'property']));
            }

            this.send(args[txt + 'model'], args[txt + 'method'], args);
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
    }

    /**
     * AJAX Requests (send)
     * Makes an AJAX call with JSON data.
     * Supports different HTTP methods (GET, POST, etc.).
     * Handles responses based on status codes (200, 201, 202, 406, etc.).
     * Calls appropriate model actions on success/failure.
     * 
     * @param {string} url 
     * @param {string} method 
     * @param {string} property 
     * @param {object} args 
     */
    send(url, method, args) {
        let parent = this;

        method = method ?? 'GET';

        $.ajax({
            dataType: 'json',
            contentType: 'application/json; charset=utf-8',
            // get the url to post to with # replacement from the objects uid
            url: url,
            // what http method should we use
            type: method.toUpperCase(),
            // what should we send as "data"
            data: args.jsonText,
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
    }


    /**
     * Model Updates (updateModel, updateModels)
     * @param {string|array} selectors 
     */
    updateModels(selectors) {
        for (let selector of this.split(selectors)) {
            this.updateModel(selector);
        };
    }

    /**
     * 
     * @param {string|DOM Element} element 
     */
    updateModel(element) {
        if (typeof element === 'string' || element instanceof String) {
            element = document.getElementById(element);
        }

        if (element) {
            this.onAttrs(undefined, { element: element, app: this, ...this.getAttr(element) });
        } else {
            console.error('Not an DOM element:', element);
        }
    }

    /**
     * Model Actions (callModelAction, callModelActions)
     * Calls functions within the model dynamically.
     * 
     * @param {string|array} modelMethodNames 
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
    }

    /**
     * Property Management (setProperty, getProperty)
     * Uses dot notation (a.b.c) to set or get properties from the model.
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
     * Property Management (setProperty, getProperty)
     * Uses dot notation (a.b.c) to set or get properties from the model.
     * 
     * @param {object} obj [this.model]
     * @param {string} dotnotation 
     * @param {mixed} value 
     */
    setProperty(obj, dotnotation, value) {
        let current = obj ?? this.model;

        if (dotnotation == '@root') {
            for (const [k, v] of Object.entries(value)) {
                // our default "methods" you can't replace
                if (!['construct', 'actions'].includes(k)) {
                    current[k] = v;
                }
            }
        } else {
            let properties = dotnotation.split('.');
            for (let i = 0; i < properties.length - 1; i++) {
                let prop = properties[i];
                if (current[prop] === undefined || current[prop] === null) {
                    current[prop] = {};
                }
                current = current[prop];
            }

            current[properties[properties.length - 1]] = value;
        }
    }

    /**
     * Property Management (setProperty, getProperty)
     * Uses dot notation (a.b.c) to set or get properties from the model.
     * 
     * @param {object} obj [this.model]
     * @param {string} dotnotation 
     * @returns mixed
     */
    getProperty(obj, dotnotation) {
        let value = obj ?? this.model;

        if (dotnotation != '@root') {
            let properties = dotnotation.split('.');

            for (let prop of properties) {
                if (value && typeof value === 'object' && value.hasOwnProperty(prop)) {
                    value = value[prop];
                } else {
                    return undefined;
                }
            }
        }

        return value;
    }

    /* Utility Methods */

    /**
     * Extracts attributes from an HTML element.
     * 
     * @param {dom element} element 
     * @returns {object}
     */
    getAttr(element) {
        let args = {};

        for (let attr of element.attributes) {
            args[attr.name] = attr.value;
        }

        return args;
    }

    /**
     * Converts a comma-separated string into an array.
     * 
     * @param {string|array} arg 
     * @returns {array}
     */
    split(arg) {
        return (!Array.isArray(arg)) ? arg.split(',') : arg;
    }

    /**
     * Displays an alert using bootbox (wrapper)
     * 
     * @param {object} args 
     */
    alert(args) {
        bootbox.alert(args);
    }
}