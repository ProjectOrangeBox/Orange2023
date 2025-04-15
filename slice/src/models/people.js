import { shared } from '../shared.js';

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
        // rv-on-click="actions.go"
        go() {
            window['@app'].go({ element: this, app: arguments[1], ...window['@app'].getAttr(this) });
        },
        // rv-on-click="actions.update | args $index"
        update(index) {
            // save this for success
            People.currentIndex = index;
            // make a copy of this so we are not working on a reference to the original record
            People.updateRecord = shared.copy(People.list[People.currentIndex]);
            // do the other stuff on the DOM element
            People.actions.doAttrsOn(this);
        },
        // on-success-action="actions.updatedSuccess"
        updatedSuccess(app, args) {
            // called from on-success-action
            //People.actions.clearValidation();
            // find the color name in People.colorDropDown based on the color id in People.updateRecord.color
            People.updateRecord.colorname = shared.searchArrayOfObjects(People.colorDropDown, People.updateRecord.color, 'id', 'name');
            // !important - copy each property 1 by 1 which triggers an update in tinybind
           shared.touch(People.updateRecord, People.list[People.currentIndex]);
        },
        // rv-on-click="actions.create"
        create() {
            // defaults - this could also be retrieved from a rest call
            People.createRecord = {
                age: 18,
                color: 6,
            }
            // do the other stuff on the DOM element
            People.actions.doAttrsOn(this);
        },
        // on-success-action="actions.createdSuccess"
        createdSuccess(app, args) {
            // called from on-success-action
            //People.actions.clearValidation();
            // put the returned primary id in the record for rests calls back to the server
            People.createRecord.id = args.json.id;
            // find the human readable version of the color id (which is a integer foreign key on the server)
            People.createRecord.colorname = shared.searchArrayOfObjects(People.colorDropDown, People.createRecord.color, 'id', 'name');
            // push the record onto the list array and tinybind will update it
            People.list.push(People.createRecord);
        },
        // rv-on-click="actions.delete | args $index"
        delete(index) {
            // save this for success
            People.currentIndex = index;
            // put a copy in the delete views record object
            People.deleteRecord = People.list[People.currentIndex];
            // do the other stuff on the DOM element
            People.actions.doAttrsOn(this);
        },
        // on-success-action="actions.deletedSuccess"
        deletedSuccess(app, args) {
            // called from on-success-action
            // pull the records from the list view array
            People.list.splice(People.currentIndex, 1);
        },
        redirect(url) {
            window['@app'].redirect(url);
        },
        clearValidation() {
            // clear out all validation
            People.validation.invalid = {};
            People.validation.show = false;
            People.validation.array = [];
        },
        doAttrsOn(element) {
            window['@app'].onAttrs({ ...window['@app'].getAttr(element) });
        },
    },
};

export default People;
