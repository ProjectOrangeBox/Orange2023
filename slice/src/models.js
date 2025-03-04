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
        redirect() {
            models.app.redirect({ element: this, ...getAttr(this), app: arguments[1] });
        },
        loadModal() {
            models.app.loadModal({ element: this, ...getAttr(this), app: arguments[1] });
        },
        cancel() {
            models.app.cancel({ element: this, ...getAttr(this), app: arguments[1] });
        },
        submit() {
            models.app.submit({ element: this, ...getAttr(this), app: arguments[1] });
        },
        close() {
            let args = { element: this, ...getAttr(this), app: arguments[1] };
            models.app.closeModal(args.name);
        },
    },
};