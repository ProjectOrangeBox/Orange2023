class gui {
    parent = undefined;

    constructor(parent) {
        this.parent = parent;
    }

    highlightErrorFields(invalidNames) {
        // first remove all that might be on the screen now
        this.removeClass('#' + this.parent.rootElement.id + ' .is-invalid', 'is-invalid');

        // then loop over the array and add the is-invalid class to
        // each matching html form element by name
        invalidNames.forEach(function (name) {
            // where should we look for this data-autoload elements?
            // find the matching form elements
            this.addClass('#' + parent.rootElement.id + ' [name="' + name + '"]', 'is-invalid');
        }, this);
    };

    showErrorDialog(tags) {
        let record = tags.json;

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
        $(selector).addClass(classes);

        return this;
    };

    removeClass(selector, classes) {
        // !todo remove jquery dep.
        $(selector).removeClass(classes);

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