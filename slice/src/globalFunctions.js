function getModelName() {
    let modelname = window.location.pathname.replace(/^\/+|\/+$/g, '').replaceAll('/', ' ');

    return modelname.replace(/(?:^\w|[A-Z]|\b\w)/g, function (word, index) {
        return index == 0 ? word.toLowerCase() : word.toUpperCase();
    }).replace(/\s+/g, '');
}

function dotToObject(dotNotationString, value) {
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
}

/*!
 * Deep merge two or more objects or arrays.
 * (c) 2023 Chris Ferdinandi, MIT License, https://gomakethings.com
 * @param   {*} ...objs  The arrays or objects to merge
 * @returns {*}          The merged arrays or objects
 */
function deepMerge(...objs) {

    /**
     * Get the object type
     * @param  {*}       obj The object
     * @return {String}      The object type
     */
    function getType(obj) {
        return Object.prototype.toString.call(obj).slice(8, -1).toLowerCase();
    }

    /**
     * Deep merge two objects
     * @return {Object}
     */
    function mergeObj(clone, obj) {
        for (let [key, value] of Object.entries(obj)) {
            let type = getType(value);
            if (clone[key] !== undefined && getType(clone[key]) === type && ['array', 'object'].includes(type)) {
                clone[key] = deepMerge(clone[key], value);
            } else {
                clone[key] = structuredClone(value);
            }
        }
    }

    // Create a clone of the first item in the objs array
    let clone = structuredClone(objs.shift());

    // Loop through each item
    for (let obj of objs) {

        // Get the object type
        let type = getType(obj);

        // If the current item isn't the same type as the clone, replace it
        if (getType(clone) !== type) {
            clone = structuredClone(obj);
            continue;
        }

        // Otherwise, merge
        if (type === 'array') {
            clone = [...clone, ...structuredClone(obj)];
        } else if (type === 'object') {
            mergeObj(clone, obj);
        } else {
            clone = obj;
        }

    }

    return clone;

}

function setProperty(obj, path, value) {
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
}

function getProperty(obj, path) {
    let properties = path.split('.');
    let current = obj;
    for (let prop of properties) {
        if (current && typeof current === 'object' && current.hasOwnProperty(prop)) {
            current = current[prop];
        } else {
            return undefined;
        }
    }
    return current;
}