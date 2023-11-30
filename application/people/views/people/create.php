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
  <div class="mb-3 float-end">
    <a class="js-close btn">Cancel</a>
    <a  data-type="POST" data-form="#createForm" data-url="<?= getUrl('people-create-post') ?>" class="js-submit btn btn-primary">Submit</a>
  </div>
</form>