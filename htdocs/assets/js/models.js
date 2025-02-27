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
        redirect(url, uid) {
            models.app.redirect({ node: this, url: url, uid: uid, ...getAttr(this) });
        },
        loadModal(name, templateUrl) {
            models.app.loadModal({ node: this, name: name, templateUrl: templateUrl, ...getAttr(this) });
        },
        cancel() {
            models.app.cancel({ node: this, ...arguments, ...getAttr(this) });
        },
        submit(httpMethod, url, record, uid) {
            models.app.submit({ node: this, httpMethod: httpMethod, url: url, record: record, uid: uid, ...getAttr(this) });
        },
        close(name) {
            models.app.closeModal({ node: this, name: name, ...getAttr(this) });
        },
    },
};