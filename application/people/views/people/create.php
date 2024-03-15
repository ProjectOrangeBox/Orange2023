<?php fig::extends('templates/base') ?>

<?php fig::block('body') ?>
<div class="masthead container">
  <form id="form">
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
      <a rv-on-click="methods.cancel" data-redirect="<?=getUrl('people') ?>" class="btn">Cancel</a>
      <a rv-on-click="methods.submit" data-url="<?=getUrl('people_post') ?>" data-type="post" data-form="form" data-redirect="<?=getUrl('people') ?>" class="btn btn-primary">Submit</a>
    </div>
  </form>
</div>
<?php fig::end() ?>

<?php fig::render() ?>