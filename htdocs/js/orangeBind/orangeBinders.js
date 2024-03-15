/**
 * These are attached directly to tinybind
 *
 * thanks to https://github.com/matthieuriolo/rivetsjs-stdlib
 *
 */

/*
	width

	Resets the css width value with the target
*/
tinybind.binders.width = function (el, value) {
	el.style.width = value;
};

/*
	height

	Resets the css height value with the target
*/
tinybind.binders.height = function (el, value) {
	el.style.height = value;
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