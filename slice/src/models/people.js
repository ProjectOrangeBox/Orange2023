var people = {
    // single records
    createRecord: {},
    updateRecord: {},
    deleteRecord: {},
    readRecord: {},

    validation: {
        show: false,
        invalid: {},
        array: [],
    },

    // list of records
    list: [],
    colorDropDown: [],

    show: {
        grid: true,
        create: false,
        read: false,
        delete: false,
        update: false,
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
            people.validation = args.json;
        },
    },

    construct(app) {
        console.log('Welcome!', app);
    },
};