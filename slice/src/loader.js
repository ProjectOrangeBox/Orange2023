class loader {
    parent = {};

    constructor(parent) {
        this.parent = parent;
    }

    model(modelUrl, appProperty, modelProperty, options, thenCall) {
        options = options || {};

        let parent = this.parent;

        this.parent.makeAjaxCall(this, {
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

                        setProperty(parent.models, appProperty, record);

                        if (typeof thenCall === 'function') {
                            thenCall(arguments);
                        }
                    } else {
                        parent.alert('Could not load model.');
                    }
                } else {
                    // show error dialog
                    parent.alert('Model returned the status [' + jqXHR.status + '].');
                }
            }
        });
    }

    template(templateUrl, elementId, options, thenCall) {
        options = options || {};

        this.parent.makeAjaxCall(this, {
            url: templateUrl,
            type: options.method || 'get',
            complete: function (jqXHR) {
                if (jqXHR.status == 200) {
                    // success
                    // replace DOM Element with responds json or html
                    this.parent.replaceElement(elementId, jqXHR);

                    if (typeof thenCall === 'function') {
                        thenCall(args);
                    }
                } else {
                    // show error dialog
                    this.parent.alert('Could not load template.');
                }
            }
        });
    }

    templateModel(templateUrl, elementId, ModelUrl, appProperty, modelProperty, options, thenCall) {
        this.template(templateUrl, elementId, options, this.model(ModelUrl, appProperty, modelProperty, options, thenCall));
    }
}
