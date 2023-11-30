<form id="deleteForm">
  <input type="hidden" name="id" value="<?=$record['id'] ?>">
  <div class="mb-3">
    <p>Are you sure you want to delete "<?=$record['firstname'] ?> <?=$record['lastname'] ?>"?</p>
  </div>
  <div class="mb-3 float-end">
    <a class="js-close btn">Cancel</a>
    <a  data-type="DELETE" data-form="#deleteForm" data-url="<?= getUrl('food-delete-post',[$record['id']]) ?>" class="js-submit btn btn-primary">Submit</a>
  </div>
</form>
<script>
<?php include 'script.js' ?>
</script>