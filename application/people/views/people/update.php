<div autoload="true" property="record" model="<?= getUrl('peopleReadOne', [$id]) ?>">
    <input type="hidden" name="id" rv-value="record.id">
    <div class="mb-3">
        <label for="frm_firstname" class="form-label">First Name</label>
        <input type="text" class="form-control" id="frm_firstname" name="firstname" rv-value="record.firstname">
    </div>
    <div class="mb-3">
        <label for="frm_lastname" class="form-label">Last Name</label>
        <input type="text" class="form-control" id="frm_lastname" name="lastname" rv-value="record.lastname">
    </div>
    <div class="mb-3">
        <label for="frm_age" class="form-label">Age</label>
        <input type="text" class="form-control" id="frm_age" name="age" rv-value="record.age">
    </div>
    <div class="mb-3">
        <div class="form-group">
            <label for="frm_color" class="form-label">Color</label>
            <select class="form-control" name="color" rv-value="record.color | default 1" preload="true" model="<?= getUrl('peoplecolordropdown') ?>" property="colors">
                <option rv-each-row="colors" rv-value="row.id" rv-text="row.name"></option>
            </select>
        </div>
    </div>
    <div class="mb-3 float-end">
        <a rv-on-click="actions.cancel" class="btn btn-light">Cancel</a>
        <a rv-on-click="actions.submit | args 'put' '<?= getUrl('peopleUpdate', ['#'], true) ?>' record.id record" on-success-refresh="true" class="btn btn-primary">Submit</a>
    </div>
</div>