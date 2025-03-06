var people = {
    // single records
    createRecord: {},
    updateRecord: {},
    deleteRecord: {},
    readRecord: {},

    validation: {},

    // list of records
    list: [],
    colorDropDown: [],

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
    },

    preload: ['main-grid', 'frm_color'],
    start: function (app) {
        console.log('Welcome!', app);
    },
};