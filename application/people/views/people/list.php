<?php fig::extends('templates/base') ?>

<?php fig::block('body') ?>

<div class="masthead container" autoload="true" model="<?= getUrl('peopleReadAll') ?>" property="records">
    <table id="table" class="table table-striped">
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">First</th>
                <th scope="col">Last</th>
                <th scope="col">Age</th>
                <th scope="col">Color</th>
                <th scope="col">
                    <button type="button" rv-on-click="actions.redirect | args '<?= getUrl('peopleCreateForm') ?>'" class="btn btn-primary"><i class="fa-solid fa-square-plus"></i></button>
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
                    <button type="button" rv-on-click="actions.redirect | args '<?= getUrl('peopleReadForm', ['#'], true) ?>' row.id" class="btn btn-primary"><i class="fa-solid fa-eye"></i></button>
                    <button type="button" rv-on-click="actions.loadModal | args '<?= getUrl('peopleUpdateForm', ['#'], true) ?>' row.id" modal-options='{"size": "modal-xl"}' class="btn btn-primary"><i class="fa-solid fa-square-pen"></i></button>
                    <button type="button" rv-on-click="actions.loadModal | args '<?= getUrl('peopleDeleteForm', ['#'], true) ?>' row.id" class="btn btn-danger"><i class="fa-solid fa-trash-can"></i></button>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<?php fig::end() ?>

<?php fig::render() ?>