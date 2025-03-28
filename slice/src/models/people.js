var People = {
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

    construct(app) {
        console.log('Welcome!', app);
    },

    // rv-on-click="actions.go"
    // rv-on-click="actions.redirect | args '/go/here'"
    actions: {
        go() {
            window['@app'].go({ element: this, app: arguments[1], ...window['@app'].getAttr(this) });
        },
        update(index) {
            // save this for success
            People.currentIndex = index;
            // make a copy of this so we are not working on a reference to the original record
            People.updateRecord = Object.assign({}, People.list[People.currentIndex]);

            // do the other stuff on the DOM element
            window['@app'].onAttrs({ ...window['@app'].getAttr(this) });
        },
        updatedSuccess(app, args) {
            // called from on-success-action
            People.actions.clearValidation();

            People.updateRecord.colorname = searchArrayOfObjects(People.colorDropDown, People.updateRecord.color, 'id', 'name');

            // trigger each property "update" so tinybind updates the list
            for (const key in People.updateRecord) {
                People.list[People.currentIndex][key] = People.updateRecord[key];
            }
        },
        create() {
            // defaults - this could also be retrieved from a rest call
            People.createRecord = {
                age: 18,
                color: 6,
            }

            // do the other stuff on the DOM element
            window['@app'].onAttrs({ ...window['@app'].getAttr(this) });
        },
        createdSuccess(app, args) {
            // called from on-success-action
            People.actions.clearValidation();

            // put the returned primary id in the record for rests calls back to the server
            People.createRecord.id = args.json.id;
            // find the human readable version of the color id (which is a integer foreign key on the server)
            People.createRecord.colorname = searchArrayOfObjects(People.colorDropDown, People.createRecord.color, 'id', 'name');

            // push the record onto the list array and tinybind will update it
            People.list.push(People.createRecord);
        },
        delete(index) {
            // save this for success
            People.currentIndex = index;
            // put a copy in the delete views record object
            People.deleteRecord = People.list[People.currentIndex];

            // do the other stuff on the DOM element
            window['@app'].onAttrs({ ...window['@app'].getAttr(this) });
        },
        deletedSuccess() {
            // called from on-success-action

            // pull the records from the list view array
            People.list.splice(People.currentIndex, 1);
        },
        redirect(url) {
            window['@app'].redirect(url);
        },
        // called by on-then
        clearValidation() {
            // clear out all validation
            People.validation.invalid = {};
            People.validation.show = false;
            People.validation.array = [];
        },
    },
};

export default People;
