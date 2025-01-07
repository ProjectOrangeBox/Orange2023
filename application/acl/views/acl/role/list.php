<?php fig::extends('templates/base') ?>

<?php fig::block('body') ?>
<div id="body" data-modal="/people/modal" data-controller="/people" data-all="/people/all">
    <div class="masthead container">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">First</th>
                    <th scope="col">Last</th>
                    <th scope="col">Age</th>
                    <th scope="col">
                        <button type="button" data-method="create" rv-on-click="methods.modal.load" data-size="modal-xl" class="btn btn-primary"><i class="fa-solid fa-square-plus"></i></button>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr rv-each-person="list">
                    <th scope="row">{person.id}</th>
                    <td>{person.firstname}</td>
                    <td>{person.lastname}</td>
                    <td>{person.age}</td>
                    <td>
                        <button type="button" data-method="update" rv-on-click="methods.modal.load" rv-data-id="person.id" data-size="modal-xl" class="btn btn-primary"><i class="fa-solid fa-square-pen"></i></button>
                        <button type="button" data-method="delete" rv-on-click="methods.modal.load" rv-data-id="person.id" class="btn btn-danger"><i class="fa-solid fa-trash-can"></i></button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <!-- Modal -->
    <div id="formModal" class="modal fade" role="dialog">
        <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content">
                <div id="modal-content" class="modal-body">
                </div>
            </div>
        </div>
    </div>
</div>
<?php fig::end() ?>

<?php fig::render() ?>