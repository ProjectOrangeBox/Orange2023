<?php fig::include('partials/modal_header', ['show' => $show, 'size' => 'sm']) ?>
<div>
    <input type="hidden" name="id" rv-value="deleteRecord.id">
    <div class="mb-3">
        <p>Are you sure you want to delete <br>"{deleteRecord.firstname} {deleteRecord.lastname}"?</p>
    </div>
    <div class="mb-3 float-end">
        <a rv-on-click="actions.go" hide="show.delete" class="btn btn-light">Cancel</a>
        <a rv-on-click="actions.go" method="delete" url="<?= getUrl('peopleDelete', ['{uid}'], true) ?>" property="deleteRecord" rv-replace-uid="deleteRecord.id" on-success-hide="show.delete" on-success-refresh="refresh.grid" on-failure-property="validation" class="btn btn-primary">Submit</a>
    </div>
</div>
<?php fig::include('partials/modal_footer') ?>