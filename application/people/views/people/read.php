<?php fig::extends('templates/base') ?>

<?php fig::block('body') ?>
<div class="masthead container" data-autoload="true" data-property="record"  data-url="<?=getUrl('people_one', $id) ?>">
  <form id="form">
    <div class="mb-3">
      <label for="frm_firstname" class="form-label">First Name</label>
      <input type="text" readonly class="form-control" id="frm_firstname" name="firstname" rv-value="record.firstname">
    </div>
    <div class="mb-3">
      <label for="frm_lastname" class="form-label">Last Name</label>
      <input type="text" readonly class="form-control" id="frm_lastname" name="lastname" rv-value="record.lastname">
    </div>
    <div class="mb-3">
      <label for="frm_age" class="form-label">Age</label>
      <input type="text" readonly class="form-control" id="frm_age" name="age" rv-value="record.age">
    </div>
    <div class="mb-3 float-end">
      <a rv-on-click="actions.cancel" data-redirect="<?=getUrl('people') ?>" class="btn btn-primary">Done</a>
    </div>
  </form>
</div>
<?php fig::end() ?>

<?php fig::render() ?>