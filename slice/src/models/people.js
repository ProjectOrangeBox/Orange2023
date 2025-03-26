var people = {
    // single records
    createRecord: {},
    updateRecord: {},
    deleteRecord: {},
    readRecord: {},

    currentIndex: 0,

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
        update(index) {
            // save this for success
            people.currentIndex = index;
            // make a copy of this so we are not working on a reference to the original record
            people.updateRecord = Object.assign({}, people.list[people.currentIndex]);

            // do the other stuff on the DOM element
            app.onAttrs({ ...app.getAttr(this) });
        },
        updatedSuccess(app, args) {
            // called from on-success-action
            people.actions.clearValidation();

            people.updateRecord.colorname = searchArrayOfObjects(people.colorDropDown, people.updateRecord.color, 'id', 'name');

            // trigger each property "update" so tinybind updates the list
            for (const key in people.updateRecord) {
                people.list[people.currentIndex][key] = people.updateRecord[key];
            }
        },
        create() {
            // defaults - this could also be retrieved from a rest call
            people.createRecord = {
                age: 18,
                color: 6,
            }

            // do the other stuff on the DOM element
            app.onAttrs({ ...app.getAttr(this) });
        },
        createdSuccess(app, args) {
            // called from on-success-action
            people.actions.clearValidation();

            // put the returned primary id in the record for rests calls back to the server
            people.createRecord.id = args.json.id;
            // find the human readable version of the color id (which is a integer foreign key on the server)
            people.createRecord.colorname = searchArrayOfObjects(people.colorDropDown, people.createRecord.color, 'id', 'name');

            // push the record onto the list array and tinybind will update it
            people.list.push(people.createRecord);
        },
        delete(index) {
            // save this for success
            people.currentIndex = index;
            // put a copy in the delete views record object
            people.deleteRecord = people.list[people.currentIndex];

            // do the other stuff on the DOM element
            app.onAttrs({ ...app.getAttr(this) });
        },
        deletedSuccess() {
            // called from on-success-action

            // pull the records from the list view array
            people.list.splice(people.currentIndex, 1);
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