var people = {
    // single records
    createRecord: {},
    updateRecord: {},
    deleteRecord: {},
    readRecord: {},

    validation: {},

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
    },

    refresh: {
        grid: true,
        colordropdown: true,
    },

    // rv-on-click="actions.redirect"
    // rv-on-click="actions.redirect | args '/go/here'"
    actions: {
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
    },

    //preload: ['main-grid', 'frm_color'],

    start(app) {
        console.log('Welcome!', app);
    },
};