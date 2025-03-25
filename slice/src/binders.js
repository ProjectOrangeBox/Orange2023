/**
 * These are attached directly to tinybind
 *
 * thanks to https://github.com/matthieuriolo/rivetsjs-stdlib
 *
 */

/*
    rv-width=""

    Resets the css width value with the target
*/
tinybind.binders.width = function (el, value) {
    el.style.width = value;
};

/*
    rv-height=""

    Resets the css height value with the target
*/
tinybind.binders.height = function (el, value) {
    el.style.height = value;
};

/*
    rv-theme-modal-show="model.property"

    boolean T/F
*/
tinybind.binders['add-show-class'] = function (el, value) {
    let elClass = " " + el.className + " ";
    let classname = 'd-none';

    if (value === elClass.indexOf(" " + classname + " ") > -1) {
        if (elClass.indexOf(" " + classname + " ") == -1) {
            el.className = el.className + " " + classname;
        } else {
            el.className = elClass.replace(" " + classname + " ", ' ').trim();
        }
    }
};

/*
    rv-theme-modal-show="model.property"

    boolean T/F
*/
tinybind.binders['theme-modal-show'] = function (el, value) {
    if (!window.modalStorage) {
        window.modalStorage = {};
    }

    if (!el.id) {
        el.id = uuidv4();
    }

    if (!window.modalStorage[el.id]) {
        window.modalStorage[el.id] = new bootstrap.Modal('#' + el.id);
    }

    if (value) {
        window.modalStorage[el.id].show();
    } else {
        window.modalStorage[el.id].hide();
    }
};

tinybind.binders['refresh'] = function (el, value) {
    window['@tinybind'].updateModel(el);
};

/*
    tiny integer (0/1) checkbox

    <input type="checkbox" name="enabled" value="1" rv-intcheck="record.enabled"/>
*/
tinybind.binders.intcheck = {
    publishes: true,
    priority: 2000,

    bind: function (el) {
        var self = this;
        if (!this.callback) {
            this.callback = function () {
                self.publish();
            };
        }
        el.addEventListener('change', this.callback);
    },
    unbind: function (el) {
        el.removeEventListener('change', this.callback);
    },
    routine: function (el, value) {
        el.checked = (el.value === value);
    },
    getValue: function (t) {
        return (t.checked) ? t.value : 0;
    }
};

tinybind.binders.select = {
    publishes: true,
    priority: 2000,

    bind: function (el) {
        var self = this;

        if (!this.callback) {
            this.callback = function () {
                self.publish();
            };
        }
        
        el.addEventListener('change', this.callback);
    },
    unbind: function (el) {
        el.removeEventListener('change', this.callback);
    },
    routine: function (el, value) {
        // do nothing
    },
    getValue: function (el) {
        const attr = app.getAttr(el);

        app.setProperty(app.model, attr['select-property'], el.options[el.selectedIndex].text);

        return el.value;
    }

}