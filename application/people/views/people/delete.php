<div data-autoload="true" data-property="record" data-url="<?= getUrl('peopleReadOne', [$id]) ?>">
  <form id="form">
    <input type="hidden" name="id" rv-value="record.id">
    <div class="mb-3">
      <p>Are you sure you want to delete "{record.firstname} {record.lastname}"?</p>
    </div>
    <div class="mb-3 float-end">
      <a rv-on-click="actions.cancel" data-modal="true" class="btn">Cancel</a>
      <a rv-on-click="actions.submit" data-refresh="true" data-url="<?= getUrl('peopleDelete', [$id]) ?>" data-method="delete" data-form="form" class="btn btn-primary">Submit</a>
    </div>
  </form>
</div>