<?php fig::extends('templates/base') ?>

<?php fig::block('body') ?>

<div class="masthead container" sd-autoload="true" sd-model="<?= getUrl('peopleReadAll') ?>" sd-property="records">
    <table id="table" class="table table-striped">
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">First</th>
                <th scope="col">Last</th>
                <th scope="col">Age</th>
                <th scope="col">Color</th>
                <th scope="col">
                    <button type="button" rv-on-click="actions.redirect" sd-url="<?= getUrl('peopleCreateForm') ?>" class="btn btn-primary"><i class="fa-solid fa-square-plus"></i></button>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr rv-each-row="records">
                <th scope="row">{row.id}</th>
                <td>{row.firstname}</td>
                <td>{row.lastname}</td>
                <td>{row.age}</td>
                <td>{row.colorname}</td>
                <td>
                    <button type="button" rv-on-click="actions.redirect" sd-url="<?= getUrl('peopleReadForm', ['#'], true) ?>" rv-sd-id="row.id" class="btn btn-primary"><i class="fa-solid fa-eye"></i></button>
                    <button type="button" rv-on-click="actions.loadModal" sd-modal-template="<?= getUrl('peopleUpdateForm', ['#'], true) ?>" rv-sd-id="row.id" sd-modal-options='{"size": "modal-xl"}' class="btn btn-primary"><i class="fa-solid fa-square-pen"></i></button>
                    <button type="button" rv-on-click="actions.loadModal" sd-modal-template="<?= getUrl('peopleDeleteForm', ['#'], true) ?>" rv-sd-id="row.id" class="btn btn-danger"><i class="fa-solid fa-trash-can"></i></button>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<?php fig::end() ?>

<?php fig::render() ?>