export function getModelName() {
    /*
    convert:
     /people to People
     /people/foods to People_Foods

    */
    let modelname = window.location.pathname.replace(/^\/+|\/+$/g, '').replaceAll('/', ' ');

    modelname.replace(/(?:^\w|[A-Z]|\b\w)/g, function (word, index) {
        return index == 0 ? word.toLowerCase() : word.toUpperCase();
    }).replace(/\s+/g, '_');

    return modelname.charAt(0).toUpperCase() + modelname.slice(1);
}

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
