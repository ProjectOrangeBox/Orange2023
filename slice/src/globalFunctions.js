function getModelName() {
    let modelname = window.location.pathname.replace(/^\/+|\/+$/g, '').replaceAll('/', ' ');

    return modelname.replace(/(?:^\w|[A-Z]|\b\w)/g, function (word, index) {
        return index == 0 ? word.toLowerCase() : word.toUpperCase();
    }).replace(/\s+/g, '');
}
