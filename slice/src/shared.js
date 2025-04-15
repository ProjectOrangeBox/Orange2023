export class shared {
    static searchArrayOfObjects(array, id2Match, idKey, nameKey) {
        let matched = undefined;

        idKey = idKey ?? 'id';
        nameKey = nameKey ?? 'name';

        // find the select text name not the id
        for (const key in array) {
            if (array[key][idKey] == id2Match) {
                matched = array[key][nameKey];
                break;
            }
        }
        return matched;
    }

    static copy(obj) {
        return Object.assign({}, obj);
    }

    static touch(src, dest) {
        for (const key in src) {
            dest[key] = src[key];
        }
    }

    static urlReplace() {
        let url = undefined

        for (var prop in arguments) {
            if (url == undefined) {
                url = arguments[prop];
            } else {
                url = url.replaceAll('{' + prop  + '}', arguments[prop]);
            }
        }
    
        return url;
    }
}