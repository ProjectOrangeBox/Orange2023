class Gui {
    parent = undefined;

    constructor(parent) {
        this.parent = parent;
    }

    notAcceptable(args) {
        let json = args.json;

        if (json.keys) {
            this.parent.model.validation =json.keys;
        }

        // show error dialog
        this.showErrorDialog(json);
    };

    removeIsInvalid(selector) {
        this.removeClass(selector + ' .is-invalid', 'is-invalid');
    };

    showErrorDialog(record) {
        // this responds is json so we grab the values or use the defaults provided
        // https://bootboxjs.com/documentation.html
        record.size = record.size || 'large'; // large alert
        record.title = record.title || 'Your Form Has The Following Errors'; // default alert title
        record.centerVertical = record.centerVertical || true; // default center vertically
        record.closeButton = record.closeButton || false; // default hide close button
        record.wrapPrefix = record.wrapPrefix || '<i class="fa-solid fa-triangle-exclamation"></i> '
        record.wrapSuffix = record.wrapSuffix || '</br>';

        // format the json errors (array) 
        // here not on the server for display in the next step
        record.message = this.wrap(record.errors, record.wrapPrefix, record.wrapSuffix);

        // show the bootbox alert
        this.parent.alert(record);
    };

    addClass(selector, classes) {
        // !todo remove jquery dep.
        $('#' + selector).addClass(classes);

        return this;
    };

    removeClass(selector, classes) {
        // !todo remove jquery dep.
        $('#' + selector).removeClass(classes);

        return this;
    };

    wrap(elements, preEach, postEach, preAll, postAll, index) {
        // set up built output
        let string = '';

        // setup defaults
        preEach = preEach || '';
        postEach = postEach || '';
        preAll = preAll || '';
        postAll = postAll || '';

        // the index to use from the array
        index = index || 'text';

        // if elements is not an array then make it one
        if (!Array.isArray(elements)) {
            elements = [elements];
        }

        // for each element wrap it with preEach & postEach
        elements.forEach(function (element) {
            // get the index if it's an array or just the element itself if it is not
            // do the actual wrapping
            string += preEach + (element[index] || element) + postEach;
        })

        // wrap the built string
        return preAll + string + postAll;
    };
}