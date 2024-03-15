<div data-autoload="true" data-model="<?= getUrl('people_one', [$id]) ?>" data-property="record">
  <form id="form">
    <input type="hidden" name="id" rv-value="record.id">
    <div class="mb-3">
      <label for="frm_firstname" class="form-label">First Name</label>
      <input type="text" class="form-control" id="frm_firstname" name="firstname" rv-value="record.firstname">
    </div>
    <div class="mb-3">
      <label for="frm_lastname" class="form-label">Last Name</label>
      <input type="text" class="form-control" id="frm_lastname" name="lastname" rv-value="record.lastname">
    </div>
    <div class="mb-3">
      <label for="frm_age" class="form-label">Age</label>
      <input type="text" class="form-control" id="frm_age" name="age" rv-value="record.age">
    </div>
    <div class="mb-3 float-end">
      <a rv-on-click="methods.cancel" data-modal="true" class="btn">Cancel</a>
      <a rv-on-click="methods.submit" data-refresh="true" data-modal="true" data-url="<?= getUrl('people_put', [$id]) ?>" data-type="put" data-form="form" class="btn btn-primary">Submit</a>
    </div>
  </form>
</div>