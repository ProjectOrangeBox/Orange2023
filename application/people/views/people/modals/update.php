<?php fig::include('partials/modal_header', ['show' => $show, 'size' => 'xl']) ?>
<div>
    <input type="hidden" name="id" rv-value="updateRecord.id">
    <div class="mb-3">
        <label for="frm_firstname" class="form-label">First Name</label>
        <input type="text" rv-class-is-invalid="validation.invalid.firstname" class="form-control" id="frm_firstname" name="firstname" rv-value="updateRecord.firstname">
    </div>
    <div class="mb-3">
        <label for="frm_lastname" class="form-label">Last Name</label>
        <input type="text" rv-class-is-invalid="validation.invalid.lastname" class="form-control" id="frm_lastname" name="lastname" rv-value="updateRecord.lastname">
    </div>
    <div class="mb-3">
        <label for="frm_age" class="form-label">Age</label>
        <input type="text" rv-class-is-invalid="validation.invalid.age" class="form-control" id="frm_age" name="age" rv-value="updateRecord.age">
    </div>
    <div class="mb-3">
        <div class="form-group">
            <label for="frm_color" class="form-label">Color</label>
            <select rv-refresh="refresh.colordropdown" rv-class-is-invalid="validation.invalid.color" class="form-control" name="color" rv-value="updateRecord.color | default 1" model="<?= getUrl('peopleColorDropdown') ?>" property="colorDropDown">
                <option rv-each-row="colorDropDown" rv-value="row.id" rv-text="row.name"></option>
            </select>
        </div>
    </div>
    <div class="mb-3 float-end">
        <a rv-on-click="actions.go" hide="show.update" then="actions.clearValidation" class="btn btn-light">Cancel</a>
        <a rv-on-click="actions.go" method="put" url="<?= getUrl('peopleUpdate', ['{id}'], true) ?>" property="updateRecord" rv-replace-id="updateRecord.id" on-success-hide="show.update" on-success-refresh="refresh.grid" then="actions.clearValidation" class="btn btn-primary">Submit</a>
    </div>
</div>
<?php fig::include('partials/modal_footer') ?>