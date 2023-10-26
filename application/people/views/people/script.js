$(".js-close").on("click", function () {
  formModal.hide();
});

$(".js-submit").on("click", function () {
  $.ajax({
    type: $(this).data("type"),
    url: $(this).data("url"),
    data: convertFormToJSON($(this).data("form")),
    statusCode: {
      // success but not a valid response
      200: function () {
        bootbox.alert("200 is an invalid response.");
      },
      // Created
      201: function () {
        formModal.hide();
        location.reload();
      },
      // Accepted
      202: function () {
        formModal.hide();
        location.reload();
      },
      // Not Acceptable
      406: function (jqXHR) {
        bootbox.alert(jqXHR.responseJSON);
      },
      default: function () {
        bootbox.alert("Record Access Issue.");
      },
    },
  });
});

function convertFormToJSON(form) {
  // Encodes the set of form elements as an array of names and values.
  const array = $(form).serializeArray();
  const json = {};

  $.each(array, function () {
    json[this.name] = this.value || "";
  });

  return json;
}
