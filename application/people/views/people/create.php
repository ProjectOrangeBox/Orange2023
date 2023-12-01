<form id="createForm">
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
    <a class="js-close btn">Cancel</a>
    <a  data-type="POST" data-form="#createForm" data-url="<?= getUrl('people-create-post') ?>" class="js-submit btn btn-primary">Submit</a>
  </div>
</form>