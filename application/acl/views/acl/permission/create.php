<?php fig::extends('templates/base') ?>

<?php fig::block('body') ?>
<div class="container">
  <form id="modal-form" style="margin-top: 128px">
    <input type="hidden" name="id" rv-value="model.id">
    <div class="mb-3">
      <label class="form-label" for="key">Key</label>
      <input type="text" class="form-control" name="key" value="">
    </div>
    <div class="mb-3">
      <label class="form-label" for="description">Description</label>
      <input type="text" class="form-control" name="description" value="">
    </div>
    <div class="mb-3">
      <label class="form-label" for="group">Group</label>
      <input type="text" class="form-control" name="group" value="">
    </div>
    <div class="mb-3 float-end">
      <a rv-on-click="methods.cancel" data-redirect="/acl/permission" class="btn">Cancel</a>
      <a rv-on-click="methods.modal.submit" data-type="post" data-form="modal-form" class="btn btn-primary">Submit</a>
    </div>
  </form>
</div>
<?php fig::end() ?>

<?php fig::render() ?>
