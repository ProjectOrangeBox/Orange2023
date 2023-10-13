<form id="createForm">
  <div class="mb-3">
    <label for="firstname" class="form-label">First Name</label>
    <input type="text" class="form-control" name="firstname">
  </div>
  <div class="mb-3">
    <label for="lastname" class="form-label">Last Name</label>
    <input type="text" class="form-control" name="lastname">
  </div>
  <div class="mb-3">
    <label for="age" class="form-label">Age</label>
    <input type="text" class="form-control" name="age">
  </div>
  <a data-form="#createForm" data-url="<?= getUrl('people-create-post') ?>" class="js-submit btn btn-primary">Submit</a>
</form>
<script>
  function convertFormToJSON(form) {
    const array = $(form).serializeArray(); // Encodes the set of form elements as an array of names and values.
    const json = {};
    $.each(array, function() {
      json[this.name] = this.value || "";
    });
    return json;
  }
  $('.js-submit').on('click', function() {
    $.ajax({
      type: "POST",
      url: $(this).data('url'),
      data: convertFormToJSON($(this).data('form')),
      statusCode: {
        201: function() {
          formModal.hide();
        },
        406: function(jqXHR, textStatus, errorThrown) {
          bootbox.alert(jqXHR.responseJSON.errors);
        },
        default: function() {
          bootbox.alert('Could Not Create Record.');
        }
      }
    })
  });
</script>