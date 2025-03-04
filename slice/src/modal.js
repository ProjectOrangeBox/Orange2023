// create the modal object
class Modal {
    // modal is open
    open = false;
    // modal id as string
    id = '';
    // reference to modal
    ref = undefined;
    // store the config sent in
    args = {};
    // html loaded?
    htmlLoaded = false;
    // name of this Modal
    name = '';
    element = undefined;

    constructor(name, args) {
        this.name = name;
        this.args = args || {};

        this.id = 'modal-bootstrap-' + name;

        // add the html to the page
        this.appendHTMLto(this.id, args.element);
        this.load();
    }

    // Append the modal to the DOM
    appendHTMLto(id, element) {
        // Create the Modal Elements
        let element_a = document.createElement('div');
        let element_b = document.createElement('div');
        let element_c = document.createElement('div');
        let element_d = document.createElement('div');

        // Set Attributes
        element_a.setAttribute('id', id);
        element_a.setAttribute('class', 'modal fade');
        element_a.setAttribute('tabindex', '-1');

        element_b.setAttribute('class', 'modal-dialog modal-dialog-scrollable modal-dialog-centered');

        element_c.setAttribute('class', 'modal-content');

        element_d.setAttribute('id', id + '-content');
        element_d.setAttribute('class', 'modal-body');

        // Append Children
        element.replaceWith(element_a);
        element_a.appendChild(element_b);
        element_b.appendChild(element_c);
        element_c.appendChild(element_d);
    }

    // resize modal
    resize(size) {
        // resize modal (we only have 1 so it is by id) or default to large
        this.args.app.gui.removeClass(this.id, 'modal-xl modal-lg modal-md modal-sm').addClass(this.id, size || 'modal-lg');
    }

    // put the returned html into the modal
    content(html) {
        document.getElementById(this.id + '-content').innerHTML = html;
    }

    // load into a bootstrap modal
    load() {
        let modal = this;

        this.args.app.makeAjaxCall({
            url: this.args.templateUrl,
            type: this.args.method || 'get',
            complete: function (jqXHR) {
                // if the responds status is
                if (jqXHR.status == 200) {
                    // success

                    // resize modal (we only have 1 so it is by id) or default to large
                    modal.resize(modal.args.options.size || 'xl');

                    // put the returned html into the modal
                    modal.content(jqXHR.responseText);

                    modal.htmlLoaded = true;

                    app.rebind();

                    // rebind the modal which now contain the new html
                    //tinybind.bind(document.getElementById(modal.id), app);
                } else {
                    // any other response code displays a error
                    app.methods.alert('Could not load modal.');
                }
            },
        });
    }

    // hide the modal
    hide() {
        if (this.open) {
            this.ref.hide();
            this.open = false;
            this.args.app.gui.removeIsInvalid(this.id);
        }
    }

    // show the modal
    show() {
        if (!this.ref) {
            this.ref = new bootstrap.Modal('#' + this.id, this.args);
        }

        this.args.app.gui.removeIsInvalid(this.id);
        this.ref.show();
        this.open = true;
    }

    removeClass(selector, classes) {
        // !todo remove jquery dep.
        $('#' + selector).removeClass(classes);

        return this;
    };
}