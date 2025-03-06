<div id="form-create" class="masthead container d-none">
    <div class="mb-3">
        <label for="frm_firstname" class="form-label">First Name</label>
        <input type="text" rv-class-is-invalid="validation.firstname" class="form-control" id="frm_firstname" name="firstname" rv-value="createRecord.firstname">
    </div>
    <div class="mb-3">
        <label for="frm_lastname" class="form-label">Last Name</label>
        <input type="text" rv-class-is-invalid="validation.lastname" class="form-control" id="frm_lastname" name="lastname" rv-value="createRecord.lastname">
    </div>
    <div class="mb-3">
        <label for="frm_age" class="form-label">Age</label>
        <input type="text" rv-class-is-invalid="validation.age" class="form-control" id="frm_age" name="age" rv-value="createRecord.age">
    </div>
    <div class="mb-3">
        <div class="form-group">
            <label for="frm_color" class="form-label">Color</label>
            <select id="frm_color" name="color" rv-class-is-invalid="validation.color" class="form-control" rv-value="createRecord.color | default 4" preload="true" model="<?= getUrl('peopleColorDropdown') ?>" property="colorDropDown">
                <option rv-each-row="colorDropDown" rv-value="row.id" rv-text="row.name"></option>
            </select>
        </div>
    </div>
    <div class="mb-3 float-end">
        <a rv-on-click="actions.swap" hide="form-create" show="main-grid" class="btn btn-light">Cancel</a>
        <a rv-on-click="actions.submit" method="post" url="<?= getUrl('peopleCreate') ?>" property="createRecord" on-success-hide="form-create" on-success-show="main-grid" on-success-refresh="main-grid" class="btn btn-primary">Submit</a>
    </div>
</div>