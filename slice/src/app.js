class App {
    // DOM id to attach to
    id;
    // DOM id element
    appElement;
    // model we are using
    model;
    // app internal storage
    storage = {};
    // ajax call mapping - action to call
    sendMapping = {
        // ok
        200: { status: 'ok', prefix: 'success' },
        // 201 Created
        201: { status: 'created', prefix: 'success' },
        // 202 Accepted
        202: { status: 'accepted', prefix: 'success' },
        // 406 Not Acceptable
        406: { status: 'not-acceptable', prefix: 'failure' },
        // ¯\_(ツ)_/¯
        default: { status: 'unknown', prefix: undefined },
    };

    /**
     * Takes a DOM element ID and a model.
     * Stores references to the root element, the model,
     * exposes the instance globally, calls the model's start function (if exists),
     * and binds the model to the DOM using tinybind.
     * 
     * @param {string} id - DOM element ID
     * @param {object} model - Data model
     */
    constructor(id, model) {
        this.id = id;
        this.appElement = document.getElementById(id);
        this.model = model;

        // Expose the instance globally for easy access
        window['@app'] = this;

        // If a start function is defined in the model, execute it
        if (model?.construct) {
            model.construct(this);
        }

        this.rebind();
    }

    /**
     * Binds the model to the DOM using tinybind.
     */
    rebind() {
        if (this.appElement) {
            tinybind.bind(this.appElement, this.model);
        } else {
            console.error('app element invalid.');
        }
    }

    /**
     * Redirects the page to the provided URL.
     * 
     * @param {string} url 
     */
    redirect(url) {
        if (url) {
            window.location.href = url;
        }
    }

    /**
     * Determines if a redirection or another action is needed.
     * 
     * @param {object} args 
     */
    go(args) {
        this.onAttrs(args);
    }

    /**
     * wrapper for successful ajax response
     * do the on-success-* attributes
     * 
     * @param {object} args - Arguments containing action keys and values
     */
    onSuccessAttrs(args) {
        this.onAttrs(args, 'on-success-');
    }

    /**
     * wrapper for failed ajax response
     * do the on-failure-* attributes
     * 
     * @param {object} args - Arguments containing action keys and values
     */
    onFailureAttrs(args) {
        this.onAttrs(args, 'on-failure-');
    }

    /**
     * Handles various actions based on event attributes.
     * 
     * @param {object} args - Arguments containing action keys and values
     * @param {string} prefixText - A prefix for attribute keys
     */
    onAttrs(args, prefixText) {
        const prefix = prefixText ?? '';

        if (args[`${prefix}action`]) {
            this.callModelActions(args[`${prefix}action`], args);
        }

        if (args[`${prefix}node`]) {
            args.json = this.getProperty(args.json, args[`${prefix}node`]);
        }

        if (args.json && args[`${prefix}property`]) {
            this.setProperty(undefined, args[`${prefix}property`], args.json);
        }

        if (args[`${prefix}model`]) {
            if (args[`${prefix}property`]) {
                args.jsonText = JSON.stringify(this.getProperty(undefined, args[`${prefix}property`]));
            }
            this.send(args[`${prefix}model`], args[`${prefix}method`], args);
        }

        if (args[`${prefix}refresh`]) {
            this.setProperties(undefined, args[`${prefix}refresh`], new Date());
        }

        if (args[`${prefix}toggle`]) {
            this.setProperties(undefined, args[`${prefix}toggle`], !this.getProperty(undefined, args[`${prefix}toggle`]));
        }

        if (args[`${prefix}hide`]) {
            this.setProperties(undefined, args[`${prefix}hide`], false);
        }

        if (args[`${prefix}false`]) {
            this.setProperties(undefined, args[`${prefix}false`], false);
        }

        if (args[`${prefix}show`]) {
            this.setProperties(undefined, args[`${prefix}show`], true);
        }

        if (args[`${prefix}true`]) {
            this.setProperties(undefined, args[`${prefix}true`], true);
        }

        if (args[`${prefix}then`]) {
            this.callModelActions(args[`${prefix}then`], args);
        }

        if (args[`${prefix}rebind`]) {
            this.rebind();
        }

        if (args[`${prefix}redirect`]) {
            window.location.href = args[`${prefix}redirect`];
        }

        if (args[`${prefix}reload`]) {
            location.reload();
        }
    }

    /**
     * Makes an AJAX call with JSON data.
     * 
     * @param {string} url 
     * @param {string} method 
     * @param {object} args 
     */
    send(url, method = 'GET', args = {}) {
        const xhr = new XMLHttpRequest();

        xhr.open(method.toUpperCase(), url);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.responseType = 'json';

        xhr.onload = () => {
            args.xhr = xhr;
            args.json = xhr.response;

            const mapping = this.sendMapping[xhr.status] || this.sendMapping.default;

            // ie. on-created-*
            if (mapping.status) {
                this.onAttrs(args, 'on-' + mapping.status + '-');
            }

            // ie. on-success-*
            if (mapping.prefix) {
                this.onAttrs(args, 'on-' + mapping.prefix + '-');
            }
        };

        xhr.onerror = () => {
            args.xhr = xhr;
            args.json = xhr.response;

            console.error('Network Error');

            this.onAttrs(args, 'on-error-');
        };

        xhr.send(args.jsonText ?? undefined);
    }

    /**
     * Updates multiple models based on selectors.
     * 
     * @param {string|Array} selectors 
     */
    updateModels(selectors) {
        for (const selector of this.split(selectors)) {
            this.updateModel(selector);
        }
    }

    /**
     * Updates a model from a DOM element or its ID.
     * 
     * @param {string|HTMLElement} element 
     */
    updateModel(element) {
        if (typeof element === 'string') {
            element = document.getElementById(element);
        }

        if (element) {
            this.onAttrs({ element, app: this, ...this.getAttr(element) });
        } else {
            console.error('Not a DOM element:', element);
        }
    }

    /**
     * Calls one or more model actions.
     * 
     * @param {string|Array} modelMethodNames 
     * @param {object} args 
     */
    callModelActions(modelMethodNames, args) {
        for (const methodName of this.split(modelMethodNames)) {
            this.callModelAction(methodName, args);
        }
    }

    /**
     * Calls a specific model action.
     * 
     * @param {string} modelMethodName 
     * @param {object} args 
     */
    callModelAction(modelMethodName, args) {
        const action = this.getProperty(undefined, modelMethodName);
        if (typeof action === 'function') {
            action(this, args);
        } else {
            console.error(`Model action "${modelMethodName}" is not a function.`);
        }
    }

    /**
     * Updates multiple properties.
     * 
     * @param {object} obj 
     * @param {string|Array} properties 
     * @param {*} value 
     */
    setProperties(obj, properties, value) {
        for (const property of this.split(properties)) {
            this.setProperty(obj, property, value);
        }
    }

    /**
     * Sets a property using dot notation.
     * 
     * @param {object} obj - The object to modify (defaults to this.model)
     * @param {string} dotnotation - Dot notation string (e.g., "a.b.c")
     * @param {*} value - The value to set
     */
    setProperty(obj, dotnotation, value) {
        let current = obj ?? this.model;

        if (dotnotation === tinybind.rootInterface) {
            Object.entries(value).forEach(([k, v]) => {
                if (!['construct', 'actions'].includes(k)) {
                    current[k] = v;
                }
            });
        } else {
            const properties = dotnotation.split('.');
            for (let i = 0; i < properties.length - 1; i++) {
                const prop = properties[i];
                if (current[prop] === undefined || current[prop] === null) {
                    current[prop] = {};
                }
                current = current[prop];
            }
            current[properties[properties.length - 1]] = value;
        }
    }

    /**
     * Retrieves a property using dot notation.
     * 
     * @param {object} obj - The object to search (defaults to this.model)
     * @param {string} dotnotation - Dot notation string (e.g., "a.b.c")
     * @returns {*} The retrieved value, or undefined if not found
     */
    getProperty(obj, dotnotation) {
        let value = obj ?? this.model;
        if (dotnotation !== tinybind.rootInterface) {
            for (const prop of dotnotation.split('.')) {
                if (value && typeof value === 'object' && Object.prototype.hasOwnProperty.call(value, prop)) {
                    value = value[prop];
                } else {
                    return undefined;
                }
            }
        }
        return value;
    }

    /**
     * Extracts attributes from a DOM element.
     * 
     * @param {HTMLElement} element 
     * @returns {object} An object mapping attribute names to values
     */
    getAttr(element) {
        const attrs = {};

        if (element.attributes) {
            for (const attr of element.attributes) {
                attrs[attr.name] = attr.value;
            }
        } else {
            console.error('does not have attributes', element);
        }

        return attrs;
    }

    /**
     * Splits a comma-separated string into an array.
     * 
     * @param {string|Array} arg 
     * @returns {Array}
     */
    split(arg) {
        return Array.isArray(arg) ? arg : arg.split(',');
    }
}