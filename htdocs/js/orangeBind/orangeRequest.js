class orangeRequest {
	/* on construction */
	constructor(app) {
		this.app = app;

		this.setStatus(0, 'init');

		this.defaultCallbacks = {
			/* standard get layout or get model */
			200: function (data, status, xhr) {
				alert('200 (ok) callback');
			},
			/* success on create */
			201: function (data, status, xhr) {
				alert('201 (created) callback');
			},
			/* success on edit */
			202: function (data, status, xhr) {
				alert('202 (accepted) callback');
			},
			/* access to resource not allowed */
			401: function (xhr, status, error) {
				alert('401 (unauthorized) callback');
			},
			/* resource not found */
			404: function (xhr, status, error) {
				alert('404 (not found) callback');
			},
			/* error submitting resource (create, edit, delete) */
			406: function (xhr, status, error) {
				alert('406 (not accepted) callback');
			},
			/* resource conflict ie. trying to create a new resource with the same primary id */
			409: function (xhr, status, error) {
				alert('409 (conflict) callback');
			},
			/* internal server error */
			500: function (xhr, status, error) {
				alert('500 (server error) callback');
			}
		};

		this.callbacks = this.defaultCallbacks;

		/* register a complete action */
		jQuery(document).ajaxComplete(this.ajaxComplete);
	}

	ajaxComplete(event, xhr, options) {
		options.requestObj.setStatus(xhr.responseJSON.status, xhr.responseJSON.statusMsg);
	}

	setStatus(code, msg) {
		this.status = code || 0;

		if (msg) {
			this.statusMsg = msg.toLowerCase();
		} else {
			this.statusMsg = 'unknown';
		}
	}

	on(code, callback) {
		if (typeof code === 'object') {
			for (let property in code) {
				this.on(property, code[property]);
			}
		} else if (Number.isInteger(code) && typeof callback === 'function') {
			/* change the responds callback based on the returned http status code */
			this.callbacks[code] = callback;
		}

		return this;
	}

	send(method, url, data, callbacks) {
		if (this.app.config.debug) {
			console.log('#' + this.app.id + ' request::send', method, url, data);
		}

		/* did they send in any callbacks? */
		if (typeof callbacks === 'object') {
			/* alter the current callbacks */
			this.on(callbacks);
		}

		jQuery.ajax({
			method: method,
			url: url,
			data: data,
			dataType: 'json',
			cache: !this.app.config.ajaxCacheBuster,
			/* ajax cache buster? */
			async: true,
			/* always! */
			timeout: this.app.config.ajaxTimeout,
			/* 5 seconds */
			statusCode: this.callbacks,
			requestObj: this,
		});

		return this;
	}

	get(url, data, callbacks) {
		return this.send('get', url, data, callbacks);
	}

	post(url, data, callbacks) {
		return this.send('post', url, data, callbacks);
	}

	patch(url, data, callbacks) {
		return this.send('patch', url, data, callbacks);
	}

	delete(url, data, callbacks) {
		return this.send('delete', url, data, callbacks);
	}

	create(url, data, callbacks) {
		return this.send('post', url, data, callbacks);
	}

	read(url, data, callbacks) {
		return this.send('get', url, data, callbacks);
	}

	update(url, data, callbacks) {
		return this.send('patch', url, data, callbacks);
	}

	insert(url, data, callbacks) {
		return this.send('post', url, data, callbacks);
	}

} /* end class */