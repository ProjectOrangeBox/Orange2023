var models = {
    app: {},

    // single records
    createRecord: {},
    updateRecord: {},
    deleteRecord: {},
    readRecord: {},

    // list of records
    list: [],
    colorDropDown: [],

    // rv-on-click="actions.redirect"
    // rv-on-click="actions.redirect | args '/go/here'"
    actions: {
        swap() {
            models.app.swap({ element: this, ...app.getAttr(this), app: arguments[1] });
        },
        redirect() {
            models.app.redirect({ element: this, ...app.getAttr(this), app: arguments[1] });
        },
        submit() {
            models.app.submit({ element: this, ...app.getAttr(this), app: arguments[1] });
        },
    },
};