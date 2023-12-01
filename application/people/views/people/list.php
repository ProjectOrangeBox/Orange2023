<?php fig::extends('templates/base') ?>

<?php fig::section('body') ?>
<div class="masthead container">
    <table class="table table-striped">
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">First</th>
                <th scope="col">Last</th>
                <th scope="col">Age</th>
                <th scope="col">
                    <button type="button" data-size="modal-xl" data-url="<?= getUrl('people-create') ?>" class="js-url-modal btn btn-primary"><i class="fa-solid fa-square-plus"></i></button>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($people as $row) { ?>
                <tr>
                    <th scope="row"><?= $row['id'] ?></th>
                    <td><?= $row['firstname'] ?></td>
                    <td><?= $row['lastname'] ?></td>
                    <td><?= $row['age'] ?></td>
                    <td>
                        <button type="button" data-size="modal-xl" data-url="<?= getUrl('people-update', [$row['id']]) ?>" class="js-url-modal js-update btn btn-primary"><i class="fa-solid fa-square-pen"></i></button>
                        <button type="button" data-size="modal-sm" data-url="<?= getUrl('people-delete', [$row['id']]) ?>" class="js-url-modal js-delete btn btn-danger"><i class="fa-solid fa-trash-can"></i></button>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
<!-- Modal -->
<div class="modal fade" id="formModal" role="dialog">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div id="modal-body" class="modal-body"></div>
        </div>
    </div>
</div>
<?php fig::end() ?>

<?php fig::render() ?>