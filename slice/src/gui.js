class Gui {
    parent = undefined;

    constructor(parent) {
        this.parent = parent;
    }

    notAcceptable(args) {
        let json = args.json;

        if (json.keys) {
            // invalid highlighting
            this.parent.model.validation = json.keys;
        }

        if (json.array) {
            // fill in the modal and show it
            this.parent.model.validations = json.array;
            this.parent.model.show.validate = true;
        }
    };

    // extra 
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