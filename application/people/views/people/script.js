function convertFormToJSON(form) {
  const array = $(form).serializeArray(); // Encodes the set of form elements as an array of names and values.
  const json = {};
  $.each(array, function() {
    json[this.name] = this.value || "";
  });
  return json;
}
$('.js-close').on('click',function() {
  formModal.hide();
});
$('.js-submit').on('click', function() {
  $.ajax({
    type: "POST",
    url: $(this).data('url'),
    data: convertFormToJSON($(this).data('form')),
    statusCode: {
      201: function() {
        formModal.hide();
        location.reload();
      },
      202: function() {
        formModal.hide();
        location.reload();
      },
      406: function(jqXHR, textStatus, errorThrown) {
        bootbox.alert(jqXHR.responseText);
      },
      default: function() {
        bootbox.alert('Could Not Create Record.');
      }
    }
  })
});
