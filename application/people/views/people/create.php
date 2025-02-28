<?php fig::extends('templates/base') ?>

<?php fig::block('body') ?>
<div class="masthead container" autoload="true" model="<?= getUrl('peopleReadNew') ?>" property="createRecord">
    <div class="mb-3">
        <label for="frm_firstname" class="form-label">First Name</label>
        <input type="text" class="form-control" id="frm_firstname" name="firstname" rv-value="createRecord.firstname">
    </div>
    <div class="mb-3">
        <label for="frm_lastname" class="form-label">Last Name</label>
        <input type="text" class="form-control" id="frm_lastname" name="lastname" rv-value="createRecord.lastname">
    </div>
    <div class="mb-3">
        <label for="frm_age" class="form-label">Age</label>
        <input type="text" class="form-control" id="frm_age" name="age" rv-value="createRecord.age">
    </div>
    <div class="mb-3">
        <div class="form-group">
            <label for="frm_color" class="form-label">Color</label>
            <select id="frm_color" name="color" class="form-control" rv-value="createRecord.color | default 4" preload="true" model="<?= getUrl('peopleColorDropdown') ?>" property="colorDropDown">
                <option rv-each-row="colorDropDown" rv-value="row.id" rv-text="row.name"></option>
            </select>
        </div>
    </div>
    <div class="mb-3 float-end">
        <a rv-on-click="actions.redirect" url="<?= getUrl('peopleReadList') ?>" class="btn btn-light">Cancel</a>
        <a rv-on-click="actions.submit" method="post" url="<?= getUrl('peopleCreate') ?>" property="createRecord" on-success-redirect="<?= getUrl('peopleReadList') ?>" class="btn btn-primary">Submit</a>
    </div>
</div>
<?php fig::end() ?>

<?php fig::render() ?>