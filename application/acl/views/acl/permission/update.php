<?php fig::extends('templates/base') ?>

<?php fig::block('body') ?>
<div id="autoLoad" data-model="/acl/permission/<?= $id ?>" data-property="record" class="container">
  <form id="modal-form" style="margin-top: 128px">
    <input type="hidden" name="id" rv-value="model.id">
    <div class="mb-3">
      <label class="form-label" for="key">Key</label>
      <input type="text" class="form-control" name="key" rv-value="record.key">
    </div>
    <div class="mb-3">
      <label class="form-label" for="description">Description</label>
      <input type="text" class="form-control" name="description" rv-value="record.description">
    </div>
    <div class="mb-3">
      <label class="form-label" for="group">Group</label>
      <input type="text" class="form-control" name="group" rv-value="record.group">
    </div>
    <div class="mb-3">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" rv-checked="record.is_active" name="is_active">
        <label class="form-check-label" for="is_active">
          Is Active
        </label>
      </div>
    </div>
    <div class="mb-3 float-end">
      <a rv-on-click="methods.cancel" data-redirect="/acl/permission" class="btn">Cancel</a>
      <a rv-on-click="methods.modal.submit" data-type="put" rv-data-id="record.id" data-form="modal-form" class="btn btn-primary">Submit</a>
    </div>
  </form>
</div>
<?php fig::end() ?>

<?php fig::render() ?>
