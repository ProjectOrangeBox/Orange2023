<form id="modal-form">
  <input type="hidden" name="id" rv-value="model.id">
  <div class="mb-3">
    <label for="frm_firstname" class="form-label">First Name</label>
    <input type="text" class="form-control" id="frm_firstname" name="firstname" value="">
  </div>
  <div class="mb-3">
    <label for="frm_lastname" class="form-label">Last Name</label>
    <input type="text" class="form-control" id="frm_lastname" name="lastname" value="">
  </div>
  <div class="mb-3">
    <label for="frm_age" class="form-label">Age</label>
    <input type="text" class="form-control" id="frm_age" name="age" value="">
  </div>
  <div class="mb-3 float-end">
    <a rv-on-click="methods.modal.cancel" class="btn">Cancel</a>
    <a rv-on-click="methods.modal.submit" data-type="post" data-form="modal-form" class="btn btn-primary">Submit</a>
  </div>
</form>