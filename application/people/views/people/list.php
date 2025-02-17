<?php fig::extends('templates/base') ?>

<?php fig::block('body') ?>

<div class="masthead container" data-autoload="true" data-url="<?= getUrl('peopleReadAll') ?>" data-property="records">
    <!--
    <div data-preload="true" data-url="<?= getUrl('peopledropdown') ?>" data-property="dropdown">
        <span>Selected Friend: {dropdown2.selected}</span>
        <select rv-value="dropdown2.selected">
            <option rv-each-row="dropdown.friends" rv-value="row.name" rv-text="row.name"></option>
        </select>
    </div>
    -->

    <table id="table" class="table table-striped">
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">First</th>
                <th scope="col">Last</th>
                <th scope="col">Age</th>
                <th scope="col">Color</th>
                <th scope="col">
                    <button type="button" rv-on-click="actions.redirect" data-redirect="<?= getUrl('peopleCreateForm') ?>" class="btn btn-primary"><i class="fa-solid fa-square-plus"></i></button>
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
                    <button type="button" rv-on-click="actions.redirect" data-redirect="<?= getUrlSkip('peopleReadForm', '#') ?>" rv-data-id="row.id" class="btn btn-primary"><i class="fa-solid fa-eye"></i></button>
                    <button type="button" rv-on-click="actions.loadModal" data-modal="<?= getUrlSkip('peopleUpdateForm', '#') ?>" rv-data-id="row.id" data-size="modal-xl" class="btn btn-primary"><i class="fa-solid fa-square-pen"></i></button>
                    <button type="button" rv-on-click="actions.loadModal" data-modal="<?= getUrlSkip('peopleDeleteForm', '#') ?>" rv-data-id="row.id" class="btn btn-danger"><i class="fa-solid fa-trash-can"></i></button>
                </td>
            </tr>
        </tbody>
    </table>

    <!--<div data-preload="true" data-url="<?= getUrl('peopledropdown2') ?>" data-property="dropdown2"></div>-->

</div>


<?php fig::end() ?>

<?php fig::render() ?>