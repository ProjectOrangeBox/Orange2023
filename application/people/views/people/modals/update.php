<?php fig::include('partials/modal_header',compact('element_id','size')) ?>
<div>
    <input type="hidden" name="id" rv-value="updateRecord.id">
    <div class="mb-3">
        <label for="frm_firstname" class="form-label">First Name</label>
        <input type="text" rv-class-is-invalid="validation.firstname" class="form-control" id="frm_firstname" name="firstname" rv-value="updateRecord.firstname">
    </div>
    <div class="mb-3">
        <label for="frm_lastname" class="form-label">Last Name</label>
        <input type="text" rv-class-is-invalid="validation.lastname" class="form-control" id="frm_lastname" name="lastname" rv-value="updateRecord.lastname">
    </div>
    <div class="mb-3">
        <label for="frm_age" class="form-label">Age</label>
        <input type="text" rv-class-is-invalid="validation.age" class="form-control" id="frm_age" name="age" rv-value="updateRecord.age">
    </div>
    <div class="mb-3">
        <div class="form-group">
            <label for="frm_color" class="form-label">Color</label>
            <select id="frm_color" rv-class-is-invalid="validation.color" class="form-control" name="color" rv-value="updateRecord.color | default 1" preload="true" model="<?= getUrl('peopleColorDropdown') ?>" property="colorDropDown">
                <option rv-each-row="colorDropDown" rv-value="row.id" rv-text="row.name"></option>
            </select>
        </div>
    </div>
    <div class="mb-3 float-end">
        <a rv-on-click="actions.swap" hide="modal-update" then="actions.clearValidation" class="btn btn-light">Cancel</a>
        <a rv-on-click="actions.submit" method="put" url="<?= getUrl('peopleUpdate', ['{id}'], true) ?>" property="updateRecord" rv-replace-id="updateRecord.id" on-success-hide="modal-update" on-success-refresh="main-grid" class="btn btn-primary">Submit</a>
    </div>
</div>
<?php fig::include('partials/modal_footer') ?>
