// global set and get using dot notation in a string
function setProperty(obj, path, value) {
    const properties = path.split('.');
    let current = obj;
    for (let i = 0; i < properties.length - 1; i++) {
        const prop = properties[i];

        if (current[prop] === undefined || current[prop] === null) {
            current[prop] = {};
        }
        current = current[prop];
    }
    
    current[properties[properties.length - 1]] = value;
}

function getProperty(obj, path) {
    const properties = path.split('.');
    let value = obj;
    for (const prop of properties) {
        if (value && typeof value === 'object' && value.hasOwnProperty(prop)) {
            value = value[prop];
        } else {
            return undefined;
        }
    }
    return value;
}

// global capture all attributes on a element
function getAttr(that) {
    let args = {};

    for (let attr of that.attributes) {
        args[attr.name] = attr.value;
    }

    return args;
}

// get all attributes starting with a tag ie foo-
function getTags(element, tag) {
    tag = tag || app.elementTag;

    let reg = new RegExp('^' + tag, 'i'); //case insensitive mce_ pattern
    let arr = {};
    let attributes = element.attributes ? element.attributes : element;
    for (const attr in attributes) {
        if (reg.test(attr)) { //if an attribute starts with ...
            let key = attr.substr(tag.length);
            arr[key] = attributes[attr]; //push to collection
        }
    }

    // add the element to the tags
    arr.element = element;

    return arr;
}
