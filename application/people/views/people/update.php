<div data-autoload="true" data-property="record" data-url="<?= getUrl('peopleReadOne', [$id]) ?>">
    <form id="form">
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
                <select class="form-control" name="color" rv-value="record.color | default 1" data-preload="true" data-url="<?= getUrl('peoplecolordropdown') ?>" data-property="colors">
                    <option rv-each-row="colors" rv-value="row.id" rv-text="row.name"></option>
                </select>
            </div>
        </div>
        <div class="mb-3 float-end">
            <a rv-on-click="actions.cancel" data-modal="true" class="btn">Cancel</a>
            <a rv-on-click="actions.submit" data-refresh="true" data-modal="true" data-url="<?= getUrl('peopleUpdate', [$id]) ?>" data-method="put" data-form="form" class="btn btn-primary">Submit</a>
        </div>
    </form>
</div>