<div>
    <input type="hidden" name="id" rv-value="updateRecord.id">
    <div class="mb-3">
        <label for="frm_firstname" class="form-label">First Name</label>
        <input type="text" class="form-control" id="frm_firstname" name="firstname" rv-value="updateRecord.firstname">
    </div>
    <div class="mb-3">
        <label for="frm_lastname" class="form-label">Last Name</label>
        <input type="text" class="form-control" id="frm_lastname" name="lastname" rv-value="updateRecord.lastname">
    </div>
    <div class="mb-3">
        <label for="frm_age" class="form-label">Age</label>
        <input type="text" class="form-control" id="frm_age" name="age" rv-value="updateRecord.age">
    </div>
    <div class="mb-3">
        <div class="form-group">
            <label for="frm_color" class="form-label">Color</label>
            <select class="form-control" name="color" rv-value="updateRecord.color | default 1" preload="true" model="<?= getUrl('peoplecolordropdown') ?>" property="colors">
                <option rv-each-row="colors" rv-value="row.id" rv-text="row.name"></option>
            </select>
        </div>
    </div>
    <div class="mb-3 float-end">
        <a rv-on-click="actions.close | args 'updateModal'" class="btn btn-light">Cancel</a>
        <a rv-on-click="actions.submit | args 'put' '<?= getUrl('peopleUpdate', ['#'], true) ?>' updateRecord updateRecord.id" on-success-close-modal="updateModal" on-success-refresh="true" class="btn btn-primary">Submit</a>
    </div>
</div>