<div autoload="true" property="record" model="<?= getUrl('peopleReadOne', [$id]) ?>">
    <input type="hidden" name="id" rv-value="record.id">
    <div class="mb-3">
        <p>Are you sure you want to delete "{record.firstname} {record.lastname}"?</p>
    </div>
    <div class="mb-3 float-end">
        <a rv-on-click="actions.close" class="btn btn-light">Cancel</a>
        <a rv-on-click="actions.submit | args 'delete' '<?= getUrl('peopleDelete', ['#'], true) ?>' record record.id" on-success-refresh="true" class="btn btn-primary">Submit</a>
    </div>
</div>
