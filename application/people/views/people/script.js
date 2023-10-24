$('.js-close').on('click',function() {
  formModal.hide();
});

$('.js-submit').on('click', function() {
  $.ajax({
    type: $(this).data('type'),
    url: $(this).data('url'),
    data: convertFormToJSON($(this).data('form')),
    statusCode: {
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
      406: function(jqXHR, textStatus, errorThrown) {
        bootbox.alert(jqXHR.responseText);
      },
      default: function() {
        bootbox.alert('Record Access Issue.');
      }
    }
  })
});

function convertFormToJSON(form) {
  const array = $(form).serializeArray(); // Encodes the set of form elements as an array of names and values.
  const json = {};
  
  $.each(array, function() {
    json[this.name] = this.value || "";
  });

  return json;
}