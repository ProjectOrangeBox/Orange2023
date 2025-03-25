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
            app.onAttrs({ ...app.getAttr(this) });

            people.currentIndex = index;
            people.updateRecord = Object.assign({}, people.list[people.currentIndex]);
        },
        updated(app, args) {
            people.actions.clearValidation();

            // trigger each property update
            for (const key in people.updateRecord) {
                people.list[people.currentIndex][key] = people.updateRecord[key];
            }
        },
        create() {
            app.onAttrs({ ...app.getAttr(this) });

            // defaults
            people.createRecord = {
                age: 18,
                color: 6,
            }
        },
        created(app, args) {
            people.actions.clearValidation();

            people.createRecord.id = args.json.id;
            people.createRecord.colorname = searchArrayOfObjects(people.colorDropDown, people.createRecord.color, 'id', 'name');

            people.list.push(people.createRecord);
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