<?php fig::extends('templates/base') ?>

<?php fig::block('body') ?>
<div id="autoLoad" data-model="/acl/permission/all" data-property="records" class="masthead container">
    <table id="table" class="table table-striped">
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">Key</th>
                <th scope="col">Description</th>
                <th scope="col">Group</th>
                <th scope="col">Is Active</th>
                <th scope="col">
                    <button type="button" rv-on-click="methods.redirect" data-redirect="/acl/permission/create" class="btn btn-primary"><i class="fa-solid fa-square-plus"></i></button>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr rv-each-row="records">
                <th scope="row">{row.id}</th>
                <td>{row.key}</td>
                <td>{row.description}</td>
                <td>{row.group}</td>
                <td>{row.is_active}</td>
                <td>
                    <button type="button" rv-on-click="methods.redirect" data-redirect="/acl/permission/update/#" rv-data-id="row.id" class="btn btn-primary"><i class="fa-solid fa-square-pen"></i></button>
                    <button type="button" rv-on-click="methods.loadModal" data-modal="/acl/permission/delete" data-model="/acl/permission/#" rv-data-id="row.id" class="btn btn-danger"><i class="fa-solid fa-trash-can"></i></button>
                </td>
            </tr>
        </tbody>
    </table>
</div>
<?php fig::end() ?>

<?php fig::render() ?>
