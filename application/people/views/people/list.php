<?php fig::extends('templates/base') ?>

<?php fig::block('body') ?>

<div class="masthead container" autoload="true" model="<?= getUrl('peopleReadAll') ?>" property="list">
    <table id="table" class="table table-striped">
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">First</th>
                <th scope="col">Last</th>
                <th scope="col">Age</th>
                <th scope="col">Color</th>
                <th scope="col">
                    <button type="button" rv-on-click="actions.redirect" url="<?= getUrl('peopleCreateForm') ?>" class="btn btn-primary"><i class="fa-solid fa-square-plus"></i></button>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr rv-each-row="list">
                <th scope="row">{row.id}</th>
                <td>{row.firstname}</td>
                <td>{row.lastname}</td>
                <td>{row.age}</td>
                <td>{row.colorname}</td>
                <td>
                    <button type="button" rv-on-click="actions.redirect" url="<?= getUrl('peopleReadForm', ['{id}'], true) ?>" rv-replace-id="row.id" class="btn btn-primary"><i class="fa-solid fa-eye"></i></button>
                    <button type="button" rv-on-click="actions.loadModal" name="updateModal" model="<?= getUrl('peopleReadOne', ['{id}'], true) ?>" rv-replace-id="row.id" property="updateRecord" class="btn btn-primary"><i class="fa-solid fa-square-pen"></i></button>
                    <button type="button" rv-on-click="actions.loadModal" name="deleteModal" model="<?= getUrl('peopleReadOne', ['{id}'], true) ?>" rv-replace-id="row.id" property="deleteRecord" class="btn btn-danger"><i class="fa-solid fa-trash-can"></i></button>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<div preload="true" modal="<?= getUrl('peopleUpdateForm', [0]) ?>" name="updateModal" options='{"size": "modal-xl"}'></div>
<div preload="true" modal="<?= getUrl('peopleDeleteForm', [0]) ?>" name="deleteModal"></div>

<?php fig::end() ?>

<?php fig::render() ?>