var people = {
    // single records
    createRecord: {},
    updateRecord: {},
    deleteRecord: {},
    readRecord: {},

    validation: {},
    validations: [],

    watchme: false,

    // list of records
    list: [],
    colorDropDown: [],

    show: {
        grid: true,
        create: false,
        read: false,
        delete: false,
        update: false,
        validate: false,
    },

    refresh: {
        grid: true,
        colordropdown: true,
    },

    // rv-on-click="actions.redirect"
    // rv-on-click="actions.redirect | args '/go/here'"
    actions: {
        go() {
            app.go({ element: this, ...app.getAttr(this), app: arguments[1] });
        },
        swap() {
            app.swap({ element: this, ...app.getAttr(this), app: arguments[1] });
        },
        redirect() {
            app.redirect({ element: this, ...app.getAttr(this), app: arguments[1] });
        },
        submit() {
            app.submit({ element: this, ...app.getAttr(this), app: arguments[1] });
        },
        // called by on-then
        clearValidation() {
            people.validation = {};
        },
        // show validation errors
        showValidationErrors(app, args) {
            let json = args.json;

            if (json.keys) {
                // invalid highlighting
                people.validation = json.keys;
            }

            if (json.array) {
                // fill in the modal and show it
                people.validations = json.array;
                people.show.validate = true;
            }
        },
    },

    construct(app) {
        console.log('Welcome!', app);
    },
};