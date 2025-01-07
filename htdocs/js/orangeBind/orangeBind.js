/**
 * create the bind element
 * arguments:
 * id - DOM element id ie. <div id="foobar"></div>
 * configuration url - url requested to get the "base" configuration
 * template url - template url prefix
 * model url - model url prefix
 */
class orangeBinder {
	constructor(id, configUrl, templateUrl, modelUrl) {
		var parent = this;

		/* DOM element id */
		this.id = id;

		/* is tiny bound bound to anything? */
		this.bound = undefined;

		/**
		 * do we have an error - boolean true/false
		 * keep these exposed on app so tinybind can use them as a boolean
		 */
		this.error = false;
		/**
		 * "errors":{"robots":{"Name":"Name is required.","Year":"Year is required."}}}
		 * keep these exposed on app so tinybind can use them as a object
		 */
		this.errors = {};

		/**
		 * actual model storage
		 */
		this.model = {};

		/**
		 * when model is single records
		 */
		this.record = {};

		/**
		 * when model is multiple records
		 */
		this.records = [];

		/* setup config with defaults */
		this.config = new orangeCollection(this, {
			settable: ["page", "form", "user", "local", "config", "templates", "error", "errors", "model"],
			gettable: ["page", "form", "error", "errors", "model"],
			defaults: {},
			debug: false,
			configUrl: configUrl || "",
			modelUrl: modelUrl || "",
			templateUrl: templateUrl || "",
			redirect: false,
			ajaxTimeout: 5000,
			routerRoot: "/",
			storageCache: 0,
			templateCache: 0,
			clearCache: false,
			ajaxCacheBuster: false,
			tinyBind: {
				prefix: "rv",
				preloadData: true,
				rootInterface: ".",
				templateDelimiters: ["{", "}"]
			}
		});

		/**
		 * collections - alter & collect
		 */
		this.page = new orangeCollection(this);
		this.form = new orangeCollection(this);
		this.user = new orangeCollection(this);
		this.local = new orangeCollection(this);

		/* user methods storage */
		this.methods = new orangeCollection(this);

		/**
		 * user storage for events
		 * <button class="btn btn-default" type="submit" rv-on-click="events.submit">Submit</button>
		 */
		this.events = new orangeCollection(this);

		/* user storage for templates */
		this.templates = new orangeCollection(this);

		/* send ajax requests to the server */
		this.request = new orangeRequest(this);

		/* Handle the changes of the browser URL  */
		this.router = new orangeRouter(this);

		this.load = new orangeLoader(this);

		/* jQuery less DOM ready */
		document.addEventListener("DOMContentLoaded", function (e) {
			/* Setup TinyBind */
			tinybind.configure(parent.config.tinyBind);

			/* do we have a config url? */
			if (parent.config.configUrl !== "") {
				/* setup the 200 callback */
				parent.request.on(200, function (data, xhr) {
					/* merge the returned data with the blocks data */
					parent.set(data);

					/* start the router */
					parent.router.match();
					/* get the config from the server */
				}).get(parent.config.configUrl);
			} else {
				/* no config url so simply start the router */
				parent.router.match();
			}
		});
	}

	/**
	 * "Trigger" an event
	 *
	 * app.trigger('error',['we have a problem','foobar function']);
	 *
	 * wrapper for jQuery trigger
	 */
	trigger(msg, args) {
		if (this.config.debug) {
			console.log('#' + this.id + ' bind::trigger ' + msg);
		}

		jQuery('body').trigger(msg, args);
	}

	/**
	 * Setup Listener
	 *
	 * app.listener('foobar', function (e) {
	 * 	this.log('args')
	 *  this.log(e.args);
	 * });
	 *
	 * wrapper for jQuery on
	 */
	listener(msg, callback) {
		jQuery('body').on(msg, callback);
	}

	/**
	 * merge and replace data
	 */
	set(data, settable) {
		settable = settable || this.config.settable;

		this.trigger('orange::bind-set', [data, settable]);

		for (let index in settable) {
			let key = settable[index];

			if (data[key] !== undefined) {
				/**
				 * if they have alter then send them in as objects and let alter merge the contents
				 * else they replace the entire variable
				 */
				if (typeof this[key].alter === "function") {
					this[key].alter(data[key]);
				} else {
					this[key] = data[key];
				}
			}
		}

		/**
		 * these are references to the actual model
		 * so the view can use records or record
		 * or you can still use model
		 */
		this.records = this.model;
		this.record = this.model;

		this.cacheMgr(this.config);

		return this; /* allow chaining */
	}

	cacheMgr(config) {
		/* do any cache cleaning based on the sent in data */
		/* is it loaded? */
		if (storage !== undefined) {
			if (config.clearCache) {
				/* only delete our cached items */
				for (let index in storage.getKeys()) {
					if (index.substr(-5) == '.bind') {
						storage.removeItem(undefined, index);
					}
				}
			}

			/* if set clear older than X seconds... */
			if (config.olderThanCache !== undefined) {
				storage.removeOlderThan(config.olderThanCache);
			}
		}
	}

	/**
	 * get the data about this element
	 */
	get(gettable) {
		gettable = gettable || this.config.gettable;

		let collection = {};

		for (let index in gettable) {
			let key = gettable[index];

			collection[key] = typeof this[key].collect === "function" ? this[key].collect() : this[key];
		}

		this.trigger('orange::bind-get', [collection, gettable]);

		return collection;
	}

	html(html) {
		this.trigger('orange::bind-html', [html]);

		this.element().innerHTML = html;
	}

	element() {
		let element = document.getElementById(this.id);

		if (element === null) {
			console.error('Element Id "' + this.id + '" Not Found.');
		}

		return element;
	}

	unbind() {
		/* unbind tinybind if it's bound */
		if (this.bound) {
			/* tell tiny binder to unbind */
			this.bound.unbind();

			/* clear our variable out */
			this.bound = undefined;
		}

		this.trigger("orange-bind-unbind");

		return this;
	}

	bind() {
		/* pass a "clean" object */
		this.bound = tinybind.bind(this.element(), {
			error: this.error,
			errors: this.errors,
			events: this.events,
			form: this.form,
			id: this.id,
			local: this.local,
			model: this.model,
			records: this.model,
			record: this.model,
			page: this.page
		});

		this.trigger("orange-bind-bind");

		return this;
	}
} /* end class */