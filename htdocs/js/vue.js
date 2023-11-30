// setup the form modal reference
var formModal = new bootstrap.Modal("#formModal", {});

var app = Vue.createApp({
    data() {
        return {
            form: {
                id: "",
                firstname: "",
                lastname: "",
                age: "",
            },
            foods: [],
        };
    },
    methods: {
        getFoods() {
            axios
                .get("food/read")
                .then(function(response) {
                    console.log(response.data);
                    app.foods = response.data;
                })
                .catch(function(error) {
                    console.log(error);
                });
        },
        createRecord(event) {
            var url = event.target.getAttribute('data-url');

            loadModel(url);
        },
        saveCreateRecord(event) {
            sendAjax(
                $(event.target).data("type"),
                $(event.target).data("url"),
                this.toFormData(this.form)
            );
        },
        updateRecord(event) {
            console.log("update record");
            console.log(event);
            console.log(element);
        },
        deleteRecord(event) {
            console.log("delete record");
            console.log(event);
            console.log(element);
        },
        resetForm() {
            console.log("reset form");
        },
    },

    mounted() {
        console.log("mounted");
        this.getFoods();
    },
}).mount("#vueapp");

function loadModel(url) {
    console.log('load url: ' + url);

    axios
        .get(url)
        .then(function(response) {
            console.log(response);
            document.getElementById('main-modal').innerHTML = response.data;
            formModal.show();
        })
        .catch(function(error) {
            console.log(error);
            bootbox.alert("Could not load url.");
        });

    /*
    $.ajax({
        type: "get",
        url: url,
        statusCode: {
            // success but not a valid response
            200: function(jqXHR) {
                $('.modal-body').html(jqXHR);
                formModal.show();
            },
            default: function() {
                bootbox.alert("Could not load url.");
            },
        },
    });
    */
}

function sendAjax(type, url, data) {
    $.ajax({
        type: type,
        url: url,
        data: data,
        statusCode: {
            // success but not a valid response
            200: function() {
                bootbox.alert("200 is an invalid response.");
            },
            // Created
            201: function() {
                formModal.hide();
                location.reload();
            },
            // Accepted
            202: function() {
                formModal.hide();
                location.reload();
            },
            // Not Acceptable
            406: function(jqXHR) {
                bootbox.alert(jqXHR.responseJSON);
            },
            default: function() {
                bootbox.alert("Record Access Issue.");
            },
        },
    });
}