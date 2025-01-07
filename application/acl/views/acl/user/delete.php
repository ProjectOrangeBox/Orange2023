<form id="modal-form">
  <input type="hidden" name="id" rv-value="model.id">
  <div class="mb-3">
    <p>Are you sure you want to delete "{model.firstname} {model.lastname}"?</p>
  </div>
  <div class="mb-3 float-end">
    <a rv-on-click="methods.modal.cancel" class="btn">Cancel</a>
    <a rv-on-click="methods.modal.submit" data-type="delete" data-form="modal-form" class="btn btn-primary">Submit</a>
  </div>
</form>