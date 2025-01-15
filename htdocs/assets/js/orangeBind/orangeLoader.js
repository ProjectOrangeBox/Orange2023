class orangeLoader {
    /* on construction */
    constructor(app)
    {
        this.app = app;
    }
    /**
     * load a model and then...
     */
    model(modelEndPoint, then)
    {
        let orangeLoader = this;

        modelEndPoint = this.app.config.modelUrl + modelEndPoint;

        if (this.app.config.debug) {
            console.log('#' + this.app.id + ' loader::model ' + modelEndPoint);
        }

        this.app.request.on(200, function (data, status, xhr) {
            orangeLoader.app.unbind().set(data).bind();

            if (then) {
                then();
            }
        }).get(modelEndPoint);

        return this; /* allow chaining */
    }

    /**
     * load a template and then...
     */
    template(templateEndPoint, then)
    {
        let orangeLoader = this;
        let cacheKey = templateEndPoint + '.template.bind';
        let template = undefined;

        if (this.app.config.debug) {
            console.log('#' + this.app.id + ' loader::template ' + templateEndPoint);
        }

        /* is this stored in our local template cache */
        if (this.app.templates[templateEndPoint] !== undefined) {
            /* yes it is so grab it */
            template = this.app.templates[templateEndPoint];
        } else if (storage !== undefined) {
            /* is this stored in our cached data */
            template = storage.getItem(cacheKey, undefined);
        }

        /* have we already loaded the template? */
        if (template !== undefined) {
            this.app.html(template);

            if (then) {
                then();
            }
        } else {
            let url = this.app.config.templateUrl + templateEndPoint;

            /* setup retrieve model - success */
            this.app.request.on(200, function (data, status, xhr) {
                /* if storage is setup than store a copy */
                if (storage !== undefined) {
                    let cacheSeconds = data.template.cache ? data.template.cache : orangeLoader.app.config.templateCache;

                    storage.setItem(cacheKey, data.template.source, cacheSeconds);
                }

                orangeLoader.app.html(data.template.source);

                if (then) {
                    then();
                }
            }).get(url);
        }

        return this; /* allow chaining */
    }

    /**
     * load a template and then a model and then...
     */
    block(templateEndPoint, modelEndPoint, then)
    {
        let orangeLoader = this;

        if (templateEndPoint) {
            /* load the template then the model */
            this.template(templateEndPoint, function () {
                orangeLoader.model(modelEndPoint, then);
            });
        } else {
            /* just load the model */
            this.model(modelEndPoint, then);
        }

        return this; /* allow chaining */
    }

} /* end class */