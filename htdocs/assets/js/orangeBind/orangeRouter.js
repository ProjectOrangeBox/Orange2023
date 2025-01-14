	class orangeRouter {

		constructor(app) {
			this.app = app;

			/* array of routes */
			this.routes = [];

			/* reference to intervalID */
			this.intervalID = undefined;

			/* what is our current url */
			this._url = this.url();
		}

		/* get and normalize the current page url */
		url() {
			let url = this._clearSlashes(decodeURI(location.pathname + location.search));

			url = url.replace(/\?(.*)$/, '');
			url = this.app.config.routerRoot !== '/' ? url.replace(this.app.config.routerRoot, '') : url;

			return this._clearSlashes(url);
		}

		/* match the router url and call the callback if a match is found */
		match(url) {
			/* did they send in a url? if not then get the current url */
			url = url || this.url();

			/* do we have any routes to listen for? */
			if (this.routes.length) {
				if (this.app.config.debug) {
					console.log('#' + this.app.id + ' router::match', url);
				}

				/* loop though the routes */
				for (let key in this.routes) {
					let parameters = url.match(this.routes[key].re);

					if (parameters) {
						if (this.app.config.debug) {
							console.log('#' + this.app.id + ' router::match::matched', parameters, this.routes[key].re.toString());
						}

						this.app.trigger('orange::router-matched', [key, this.routes[key], parameters]);

						/* remove matched url  */
						parameters.shift();

						/* call the route callback and pass in the parameters */
						this.routes[key].callback.apply({}, parameters);

						break; /* break from for loop */
					}
				}
			}

			return this; /* allow chaining */
		}

		/* add or change a route */
		alter(regularExpression, callback) {
			if (typeof regularExpression === 'object') {
				for (let property in regularExpression) {
					this.alter(property, regularExpression[property]);
				}
			} else {
				/* add to the routes array */
				this.routes.push({
					re: this._normalizeRegularExpression(regularExpression),
					callback: callback
				});
			}

			/* turn on listening if it's not already */
			if (!this.intervalID) {
				this.start();
			}

			return this; /* allow chaining */
		}

		/* remove a single route it's url regular expression */
		remove(regularExpression) {
			let re = this._normalizeRegularExpression(regularExpression);

			for (let key in this.routes) {
				if (re.toString() == this.routes[key].re.toString()) {
					this.routes.splice(key, 1);
				}
			}

			return this; /* allow chaining */
		}

		/* delete all routes */
		flush() {
			this.routes = [];

			return this.stop(); /* allow chaining */
		}

		/* start router listener matching for changes in the url */
		start() {
			/* Do we have any routes to listen for? */
			if (this.routes.length) {
				/* if we are already listening let's just make sure we stop first */
				this.stop();

				/* we are now listening for url changes */
				this.intervalID = setInterval(this.listener, 100, this);
			}

			return this; /* allow chaining */
		}

		stop() {
			if (this.intervalID) {
				clearInterval(this.intervalID);
			}

			return this; /* allow chaining */
		}

		/*
		the interval listener
		since interval is actually calling a function the reference to "this" doesn't work
		*/
		listener(orangeRouter) {
			let url = orangeRouter.url();

			if (orangeRouter._url != url) {
				orangeRouter._url = url;

				orangeRouter.app.trigger('orange::router-changed', [url]);

				orangeRouter.match(url);
			}
		}

		/* navigate to a new url optionally specifying it as a redirect or history change */
		navigate(url, redirect) {
			url = url ? this.app.config.routerRoot + this._clearSlashes(url) : '';
			redirect = redirect ? redirect : this.app.config.redirect;

			if (this.app.config.debug) {
				console.log('#' + this.app.id + ' router::navigate ', url, redirect);
			}

			/* trigger a redirect so other javascript code knows we are redirecting */
			this.app.trigger('orange::router-navgate', [url, redirect]);

			if (redirect) {
				/* full page reload so trigger wouldn't even be picked up */
				window.location.href = url;
			} else {
				/* adds a state to the browser's session history stack redirect */
				history.pushState(null, null, url);
			}

			return this; /* allow chaining */
		}

		/* private */

		/* remove all slashes from the beginning and end of the passed url */
		_clearSlashes(url) {
			return url.toString().replace(/\/$/, '').replace(/^\//, '');
		}

		/*
		normalize the regular expression
		and convert (:any) (:num) (:hex) (:str) to actual expression values
		*/
		_normalizeRegularExpression(regularExpression) {
			/* trim / fore & aft */
			regularExpression = this._clearSlashes(regularExpression);

			/* escape / to \/ */
			regularExpression = regularExpression.replace(new RegExp('/', 'g'), "\\/");

			/* add CodeIgniter matches */
			regularExpression = regularExpression.replace(new RegExp(':any', 'g'), '[^/]+'); /* anything */
			regularExpression = regularExpression.replace(new RegExp(':num', 'g'), '[0-9]+'); /* number only */
			regularExpression = regularExpression.replace(new RegExp(':hex', 'g'), '[0-9a-f]+'); /* hex values */
			regularExpression = regularExpression.replace(new RegExp(':str', 'g'), '[0-9a-zA-Z]+'); /* str values */

			return new RegExp(regularExpression);
		}

	} /* end class */