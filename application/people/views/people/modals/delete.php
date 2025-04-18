<div>
    <input type="hidden" name="id" rv-value="deleteRecord.id">
    <div class="mb-3">
        <p>Are you sure you want to delete <br>"{deleteRecord.firstname} {deleteRecord.lastname}"?</p>
    </div>
    <div class="mb-3 float-end">
        <a rv-on-click="actions.go" hide="show.delete" class="btn btn-light">Cancel</a>
        <a rv-on-click="actions.go" method="delete" rv-model="'<?= getUrl('peopleDelete', ['{1}'], true) ?>' | replace deleteRecord.id" property="deleteRecord" on-success-hide="show.delete" on-success-action="actions.deletedSuccess" class="btn btn-primary">Submit</a>
    </div>
</div>