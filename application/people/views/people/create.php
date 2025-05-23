<div rv-show="show.create" class="masthead container">
    <div class="mb-3">
        <label for="frm_firstname" class="form-label">First Name</label>
        <input type="text" rv-class-is-invalid="validation.invalid.firstname" class="form-control" id="frm_firstname" name="firstname" rv-value="createRecord.firstname">
    </div>
    <div class="mb-3">
        <label for="frm_lastname" class="form-label">Last Name</label>
        <input type="text" rv-class-is-invalid="validation.invalid.lastname" class="form-control" id="frm_lastname" name="lastname" rv-value="createRecord.lastname">
    </div>
    <div class="mb-3">
        <label for="frm_age" class="form-label">Age</label>
        <input type="text" rv-class-is-invalid="validation.invalid.age" class="form-control" id="frm_age" name="age" rv-value="createRecord.age">
    </div>
    <div class="mb-3">
        <div class="form-group">
            <label for="frm_color" class="form-label">Color</label>
            <select rv-class-is-invalid="validation.invalid.color" class="form-control" name="color" id="frm_color" rv-value="createRecord.color | default 4">
                <option rv-each-row="colorDropDown" rv-value="row.id" rv-text="row.name"></option>
            </select>
        </div>
    </div>
    <div class="mb-3 float-end">
        <a rv-on-click="actions.go" hide="show.create" show="show.grid" action="actions.clearValidation" class="btn btn-light">Cancel</a>
        <a rv-on-click="actions.go" method="post" model="<?= getUrl('peopleCreate') ?>" property="createRecord" on-success-action="actions.clearValidation,actions.createdSuccess" on-success-hide="show.create" on-success-show="show.grid" on-failure-property="." class="btn btn-primary">Submit</a>
    </div>
</div>