export function searchArrayOfObjects(array, id2Match, idKey, nameKey) {
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

