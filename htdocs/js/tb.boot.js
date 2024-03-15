var app = {};

app.methods = {
    // load into a modal (not MODEL!)
    loadModal() {
        let data = $(this).data();

        // resize modal or default to large
        $("#modal")
            .removeClass("modal-xl modal-lg modal-md modal-sm")
            .addClass(data.size || "modal-lg");

        // get modal
        $.ajax({
            type: data.method || 'get',
            url: data.modal.replace("#", data.id || ''),
            complete: function (jqXHR, textStatus) {
                let text = jqXHR.responseText;
                let json = jqXHR.responseJSON;

                switch (jqXHR.status) {
                    case 200:
                        $("#modal-content").html(text);

                        tinybind.bind($("#modal"), app);

                        // only autoload the model on the modal
                        app.methods.autoLoadModel('#modal-content ');

                        // show it
                        app.modalRef.show();
                        break;
                    default:
                        bootbox.alert("Could not load modal.");
                }
            }
        });
    },
    hideModal(hasModal) {
        if (hasModal) {
            // remove it
            app.modalRef.hide();
        }

        // and remove it's contents
        if ($("#modal-content").length > 0) {
            $("#modal-content").html('');
        }
    },
    redirect() {
        let data = $(this).data();

        window.location.href = data.redirect.replace("#", data.id || '');
    },
    cancel() {
        let data = $(this).data();

        app.methods.hideModal(data.modal);

        if (data.redirect) {
            window.location.href = data.redirect.replace("#", data.id || '');
        }
    },
    submit() {
        let data = $(this).data();
        let formData = toJson(data.form);
        let url = data.url.replace("#", formData.id);

        $.ajax({
            type: data.type,
            url: url,
            data: JSON.stringify(formData),
            complete: function (jqXHR, textStatus) {
                let text = jqXHR.responseText;
                let json = jqXHR.responseJSON;

                switch (jqXHR.status) {
                    case 200:
                        // 200 in this case is NOT a valid response code
                        bootbox.alert("200 is an invalid response.");
                        break;
                    case 201:
                        // Created
                        app.methods.hideModal(data.modal);

                        if (data.reload) {
                            location.reload();
                        }

                        if (data.refresh) {
                            app.methods.autoLoadModel();
                        }

                        if (data.redirect) {
                            window.location.href = data.redirect;
                        }
                        break;
                    case 202:
                        // Accepted
                        app.methods.hideModal(data.modal);

                        if (data.reload) {
                            location.reload();
                        }

                        if (data.refresh) {
                            app.methods.autoLoadModel();
                        }

                        if (data.redirect) {
                            window.location.href = data.redirect;
                        }
                        break;
                    case 406:
                        // Not Acceptable
                        json.size = json.size || "large";
                        json.title = json.title || "Your Form Has The Following Errors";
                        json.centerVertical = json.centerVertical || true;
                        json.closeButton = json.closeButton || false;

                        app.methods.updateInvalidUI(json.keys);

                        bootbox.alert(json);
                        break;
                    default:
                        bootbox.alert("Record Access Issue.");

                }
            },
        });
    },
    updateInvalidUI(invalidNames) {
        $('.is-invalid').removeClass('is-invalid');

        invalidNames.forEach(function (name) {
            $('[name="' + name + '"]').addClass('is-invalid');
        });
    },
    // internal methods
    model(modelUrl, appProperty, method) {
        $.ajax({
            url: modelUrl,
            type: method || "get",
            complete: function (jqXHR, textStatus) {
                let text = jqXHR.responseText;
                let json = jqXHR.responseJSON;

                switch (jqXHR.status) {
                    case 200:
                        app[appProperty] = json[appProperty];
                        break;
                    default:
                        bootbox.alert("Model Access Issue.");
                }
            },
        });
    },
    layout(layoutUrl, elementId, method) {
        $.ajax({
            url: layoutUrl,
            type: method || "get",
            complete: function (jqXHR, textStatus) {
                if (jqXHR.status == 200) {
                    let text = jqXHR.responseText;
                    let json = jqXHR.responseJSON;

                    if (json.html !== undefined) {
                        $('#' + elementId.replace('#', '')).html(json.html);
                    } else {
                        $('#' + elementId.replace('#', '')).html(text);
                    }
                } else {
                    bootbox.alert("Layout Access Issue.");
                }
            },
        });
    },
    layoutModel(layoutUrl, elementId, method, modelUrl, modelProperty, modelMethod) {
        $.ajax({
            url: layoutUrl,
            type: method || "get",
            complete: function (jqXHR, textStatus) {
                if (jqXHR.status == 200) {
                    let text = jqXHR.responseText;
                    let json = jqXHR.responseJSON;

                    if (json.html !== undefined) {
                        $('#' + elementId.replace('#', '')).html(json.html);
                    } else {
                        $('#' + elementId.replace('#', '')).html(text);
                    }

                    // now let's call our model loading
                    app.methods.model(modelUrl, modelProperty, modelMethod);
                } else {
                    bootbox.alert("Layout Access Issue.");
                }
            },
        });
    },
    loadModelFromElement(id) {
        let data = {};

        if (typeof id === 'string' || id instanceof String) {
            data = $('#' + id.replace('#', '')).data() || {};
        } else {
            data = $(id).data() || {};
        }

        app.methods.model(data.model, data.property, data.method || "get");
    },
    autoLoadModel(prefix) {
        $((prefix || '') + '[data-autoload="true"]').each(function() {
            app.methods.loadModelFromElement(this);
        });
    },
};

/* bootstrap */
document.addEventListener("DOMContentLoaded", function () {
    // setup the bootstrap form modal reference
    app.modalRef = new bootstrap.Modal($("#modal"), {});

    // setup tinybind on the app element passing in the app object
    tinybind.bind($("#app"), app);

    // detect and load models
    app.methods.autoLoadModel();
});

// convert form into javascript object
function toJson(formId) {
    const form = document.getElementById(formId);
    const formData = new FormData(form);

    let object = {};

    formData.forEach((value, key) => {
        if (!Reflect.has(object, key)) {
            object[key] = value;
            return;
        }
        if (!Array.isArray(object[key])) {
            object[key] = [object[key]];
        }
        object[key].push(value);
    });

    return object;
}