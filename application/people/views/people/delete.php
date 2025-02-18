<div sd-autoload="true" sd-property="record" sd-model="<?= getUrl('peopleReadOne', [$id]) ?>">
    <input type="hidden" name="id" rv-value="record.id">
    <div class="mb-3">
        <p>Are you sure you want to delete "{record.firstname} {record.lastname}"?</p>
    </div>
    <div class="mb-3 float-end">
        <a rv-on-click="actions.cancel" class="btn btn-light">Cancel</a>
        <a rv-on-click="actions.submit" sd-delete-url="<?= getUrl('peopleDelete', [$id]) ?>" sd-on-success-refresh="true" class="btn btn-primary">Submit</a>
    </div>
</div>