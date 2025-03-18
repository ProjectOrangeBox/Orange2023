function getModelName() {
    let modelname = window.location.pathname.replace(/^\/+|\/+$/g, '').replaceAll('/', ' ');

    return modelname.replace(/(?:^\w|[A-Z]|\b\w)/g, function (word, index) {
        return index == 0 ? word.toLowerCase() : word.toUpperCase();
    }).replace(/\s+/g, '');
}

function uuidv4() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'
        .replace(/[xy]/g, function (c) {
            const r = Math.random() * 16 | 0,
                v = c == 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
}

if (!String.format) {
    String.format = function (format) {
        var args = Array.prototype.slice.call(arguments, 1);
        return format.replace(/{(\d+)}/g, function (match, number) {
            return typeof args[number] != 'undefined'
                ? args[number]
                : match
                ;
        });
    };
}


/**
 * let object = dotToObject('person.name','Joe');
 * 
 * @param {string} dotNotationString 
 * @param {mixed} value 
 * @returns object
 */
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