<div>
    <input type="hidden" name="id" rv-value="deleteRecord.id">
    <div class="mb-3">
        <p>Are you sure you want to delete "{deleteRecord.firstname} {deleteRecord.lastname}"?</p>
    </div>
    <div class="mb-3 float-end">
        <a rv-on-click="actions.close" name="deleteModal" class="btn btn-light">Cancel</a>
        <a rv-on-click="actions.submit | args 'delete' '<?= getUrl('peopleDelete', ['{uid}'], true) ?>' deleteRecord deleteRecord.id" on-success-close-modal="deleteModal" on-success-refresh="true" class="btn btn-primary">Submit</a>
    </div>
</div>
