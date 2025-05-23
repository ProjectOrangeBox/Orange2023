import tinybind from 'tinybind';
import * as bootstrap from 'bootstrap'

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
tinybind.binders['theme-modal-show'] = function (el, value) {
    window.modalStorage = window.modalStorage || {};

    el.id = el.id || parseInt(Math.ceil(Math.random() * Date.now()).toPrecision(24).toString().replace(".", ""));

    window.modalStorage[el.id] = window.modalStorage[el.id] || new bootstrap.Modal('#' + el.id);

    value ? window.modalStorage[el.id].show() : window.modalStorage[el.id].hide();
};

tinybind.binders['refresh'] = function (element) {
    const app = window['@app'];

    app.onAttrs({ element, app: app, ...app.getAttr(element) });
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
