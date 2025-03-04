class Loader {
    app = {};

    constructor(app) {
        this.app = app;
    }

    model(modelUrl, appProperty, modelProperty, options, thenCall) {        
        console.log(arguments);

        options = options || {};

        var parent = this;

        this.app.makeAjaxCall({
            url: modelUrl,
            type: options.method || 'get',
            complete: function (jqXHR) {
                // based on the responds code
                if (jqXHR.status == 200) {
                    // success

                    // capture the text or json from the responds
                    let jsonObject = jqXHR.responseJSON;

                    // replace the application property with the matching json property
                    if (jsonObject) {
                        let record = modelProperty ? getProperty(jsonObject, modelProperty) : jsonObject;

                        setProperty(parent.app.models, appProperty, record);

                        parent.app.rebind();

                        if (typeof thenCall === 'function') {
                            thenCall(arguments);
                        }
                    } else {
                        parent.app.alert('Could not load model.');
                    }
                } else {
                    // show error dialog
                    parent.app.alert('Model returned the status [' + jqXHR.status + '].');
                }
            }
        });
    }

    template(templateUrl, elementId, options, thenCall) {
        options = options || {};

        var parent = this;

        this.app.makeAjaxCall({
            url: templateUrl,
            type: options.method || 'get',
            complete: function (jqXHR) {
                if (jqXHR.status == 200) {
                    // success
                    // replace DOM Element with responds json or html
                    parent.app.replaceElement(elementId, jqXHR);

                    parent.app.rebind();

                    if (typeof thenCall === 'function') {
                        thenCall(args);
                    }
                } else {
                    // show error dialog
                    parent.app.alert('Could not load template.');
                }
            }
        });
    }

    templateModel(templateUrl, elementId, ModelUrl, appProperty, modelProperty, options, thenCall) {
        this.template(templateUrl, elementId, options, this.model(ModelUrl, appProperty, modelProperty, options, thenCall));
    }
}
