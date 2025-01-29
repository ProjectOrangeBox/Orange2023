<?php fig::extends('templates/base') ?>

<?php fig::block('body') ?>
<div class="masthead container" data-autoload="true" data-property="record" data-url="<?= getUrl('peopleReadNew') ?>">
    <form id="form">
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
                <select name="color" class="form-control" rv-value="record.color | default 4" data-preload="true" data-url="<?= getUrl('peopleColorDropdown') ?>" data-property="colors">
                    <option rv-each-row="colors" rv-value="row.id" rv-text="row.name"></option>
                </select>
            </div>
        </div>
        <div class="mb-3 float-end">
            <a rv-on-click="actions.cancel" data-redirect="<?= getUrl('peopleReadList') ?>" class="btn">Cancel</a>
            <a rv-on-click="actions.submit" data-url="<?= getUrl('peopleCreate') ?>" data-type="post" data-form="form" data-redirect="<?= getUrl('peopleReadList') ?>" class="btn btn-primary">Submit</a>
        </div>
    </form>
</div>
<?php fig::end() ?>

<?php fig::render() ?>