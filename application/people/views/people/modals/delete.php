<?php fig::include('partials/modal_header',compact('element_id','size')) ?>
<div>
    <input type="hidden" name="id" rv-value="deleteRecord.id">
    <div class="mb-3">
        <p>Are you sure you want to delete <br>"{deleteRecord.firstname} {deleteRecord.lastname}"?</p>
    </div>
    <div class="mb-3 float-end">
    <a rv-on-click="actions.swap" hide="modal-delete" class="btn btn-light">Cancel</a>
    <a rv-on-click="actions.submit" method="delete" url="<?= getUrl('peopleDelete', ['{uid}'], true) ?>" property="deleteRecord" rv-replace-uid="deleteRecord.id" on-success-hide="modal-delete" on-success-refresh="main-grid" class="btn btn-primary">Submit</a>
    </div>
</div>
<?php fig::include('partials/modal_footer') ?>
