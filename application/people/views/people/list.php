<?php fig::extends('templates/base') ?>

<?php fig::block('body') ?>
<div data-autoload="true" data-model="<?=getUrl('people_all') ?>" data-property="records" class="masthead container">
    <table id="table" class="table table-striped">
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">First</th>
                <th scope="col">Last</th>
                <th scope="col">Age</th>
                <th scope="col">
                    <button type="button" rv-on-click="methods.redirect" data-redirect="<?=getUrl('people_create') ?>" class="btn btn-primary"><i class="fa-solid fa-square-plus"></i></button>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr rv-each-row="records">
                <th scope="row">{row.id}</th>
                <td>{row.firstname}</td>
                <td>{row.lastname}</td>
                <td>{row.age}</td>
                <td>
                    <button type="button" rv-on-click="methods.redirect" data-redirect="<?=getUrl('people_show',['#']) ?>" rv-data-id="row.id" class="btn btn-primary"><i class="fa-solid fa-eye"></i></button>
                    <button type="button" rv-on-click="methods.loadModal" data-modal="<?=getUrl('people_update',['#']) ?>" rv-data-id="row.id" data-size="modal-xl" class="btn btn-primary"><i class="fa-solid fa-square-pen"></i></button>
                    <button type="button" rv-on-click="methods.loadModal" data-modal="<?=getUrl('people_delete',['#']) ?>" rv-data-id="row.id" class="btn btn-danger"><i class="fa-solid fa-trash-can"></i></button>
                </td>
            </tr>
        </tbody>
    </table>
</div>
<?php fig::end() ?>

<?php fig::render() ?>