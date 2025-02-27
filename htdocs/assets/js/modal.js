// create the modal object
class Modal {
    // modal is open
    open = false;
    // modal id as string
    id = '';
    // modal id as jquery selector by id
    jid = '';
    // reference to modal
    ref = undefined;
    // store the config sent in
    config = {};
    // html loaded?
    htmlLoaded = false;
    // name of this Modal
    name = '';

    constructor(name, config) {
        // save the config
        this.config = config || {};
        this.name = name;

        // unique id
        this.id = 'modal-bootstrap-' + Math.round(Math.random() * (9999999 - 1000000) + 1000000);
        this.jid = '#' + this.id;

        // add the html to the page
        this.appendHTMLto(this.id, document.body);
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
        element.appendChild(element_a);
        element_a.appendChild(element_b);
        element_b.appendChild(element_c);
        element_c.appendChild(element_d);
    }

    // resize modal
    resize(size) {
        // resize modal (we only have 1 so it is by id) or default to large
        app.methods.removeClass(this.jid, 'modal-xl modal-lg modal-md modal-sm').addClass(this.jid, size || 'modal-lg');
    }

    // put the returned html into the modal
    content(html) {
        document.getElementById(this.id + '-content').innerHTML = html;
    }

    // load into a bootstrap modal
    load(args) {
        let modal = this;

        if (modal.htmlLoaded) {
            // load a record into the modal
            app.methods.autoLoad(modal.id);

            // show the modal
            modal.show();
        } else {
            makeAjaxCall(this, {
                url: args.templateUrl,
                type: args.method || 'get',
                complete: function (jqXHR) {
                    // if the responds status is
                    if (jqXHR.status == 200) {
                        // success

                        // resize modal (we only have 1 so it is by id) or default to large
                        modal.resize(args.options.size || 'xl');

                        // put the returned html into the modal
                        modal.content(jqXHR.responseText);

                        modal.htmlLoaded = true;

                        // rebind the modal which now contain the new html
                        tinybind.bind(document.getElementById(modal.id), app);

                        // load a record into the modal
                        app.methods.autoLoad(modal.id);

                        // show the modal
                        modal.show();
                    } else {
                        // any other response code displays a error
                        app.methods.alert('Could not load modal.');
                    }
                },
            });
        }
    }

    // hide the modal
    hide() {
        if (this.open) {
            this.ref.hide();
            this.open = false;
        }
    }

    // show the modal
    show() {
        if (!this.ref) {
            this.ref = new bootstrap.Modal(this.jid, this.config);
        }

        this.ref.show();
        this.open = true;
    }
}