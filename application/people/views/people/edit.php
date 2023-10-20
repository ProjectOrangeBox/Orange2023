<form id="updateForm">
  <input type="hidden" name="id" value="<?=$record['id'] ?>">
  <div class="mb-3">
    <label for="firstname" class="form-label">First Name</label>
    <input type="text" class="form-control" name="firstname" value="<?=$record['firstname'] ?>">
  </div>
  <div class="mb-3">
    <label for="lastname" class="form-label">Last Name</label>
    <input type="text" class="form-control" name="lastname" value="<?=$record['lastname'] ?>">
  </div>
  <div class="mb-3">
    <label for="age" class="form-label">Age</label>
    <input type="text" class="form-control" name="age" value="<?=$record['age'] ?>">
  </div>
  <div class="mb-3 float-end">
    <a class="js-close btn">Cancel</a>
    <a data-type="PUT" data-form="#updateForm" data-url="<?= getUrl('people-update-post',[$record['id']]) ?>" class="js-submit btn btn-primary">Submit</a>
  </div>
</form>
<script>
<?php include 'script.js' ?>
</script>