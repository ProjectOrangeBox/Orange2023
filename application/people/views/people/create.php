<?php fig::extends('templates/base') ?>

<?php fig::block('body') ?>
<div class="masthead container" sd-autoload="true" sd-property="record" sd-model="<?= getUrl('peopleReadNew') ?>">
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
            <select id="frm_color" name="color" class="form-control" rv-value="record.color | default 4" sd-preload="true" sd-model="<?= getUrl('peopleColorDropdown') ?>" sd-property="colors">
                <option rv-each-row="colors" rv-value="row.id" rv-text="row.name"></option>
            </select>
        </div>
    </div>
    <div class="mb-3 float-end">
        <a rv-on-click="actions.cancel" sd-redirect="<?= getUrl('peopleReadList') ?>" class="btn btn-light">Cancel</a>
        <a rv-on-click="actions.submit" sd-post-url="<?= getUrl('peopleCreate') ?>" sd-property="record" sd-on-success-redirect="<?= getUrl('peopleReadList') ?>" class="btn btn-primary">Submit</a>
    </div>
</div>
<?php fig::end() ?>

<?php fig::render() ?>