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

    // rv-on-click="actions.go"
    // rv-on-click="actions.redirect | args '/go/here'"
    actions: {
        go() {
            app.go({ element: this, app: arguments[1], ...app.getAttr(this) });
        },
        redirect(url) {
            app.redirect(url);
        },
        // called by on-then
        clearValidation() {
            // clear out all validation
            people.validation.invalid = {};
            people.validation.show = false;
            people.validation.array = [];

        },
    },

    construct(app) {
        console.log('Welcome!', app);
    },
};