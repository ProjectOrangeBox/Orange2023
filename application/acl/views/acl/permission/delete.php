<form id="modal-form">
  <input type="hidden" name="id" rv-value="record.id">
  <div class="mb-3">
    <p>Are you sure you want to delete {record.key}: {record.description}?</p>
  </div>
  <div class="mb-3 float-end">
    <a rv-on-click="methods.cancel" data-modal="true" class="btn">Cancel</a>
    <a rv-on-click="methods.submit" data-modal="true" data-url="/acl/permission/#" data-type="delete" data-form="modal-form" rv-data-id="record.id" class="btn btn-primary">Submit</a>
  </div>
</form>
