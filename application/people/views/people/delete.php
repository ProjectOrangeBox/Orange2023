<div id="modalRecord" data-model="<?= getUrl('people_one', [$id]) ?>">
  <form id="form">
    <input type="hidden" name="id" rv-value="record.id">
    <div class="mb-3">
      <p>Are you sure you want to delete "{record.firstname} {record.lastname}"?</p>
    </div>
    <div class="mb-3 float-end">
      <a rv-on-click="methods.cancel" data-modal="true" class="btn">Cancel</a>
      <a rv-on-click="methods.submit" data-modal="true" data-url="<?= getUrl('people_del', [$id]) ?>" data-type="delete" data-form="form" class="btn btn-primary">Submit</a>
    </div>
  </form>
</div>