<?php fig::extends('templates/base') ?>

<?php fig::section('body') ?>
<div id='vueapp'>
    <div class="masthead container">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">First</th>
                    <th scope="col">Last</th>
                    <th scope="col">Age</th>
                    <th scope="col">
                        <button @click="createRecord($event)" type="button" data-url="<?= getUrl('food-create') ?>" class="btn btn-primary">
                            <i class="fa-solid fa-square-plus"></i>
                        </button>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="food in foods" ::key="id">
                    <th scope="row">{{ food.id }}</th>
                    <td>{{ food.firstname }}</td>
                    <td>{{ food.lastname }}</td>
                    <td>{{ food.age }}</td>
                    <td>
                        <button type="button" v-on:click="updateRecord" v-bind:data-pid="food.id" class="btn btn-primary">
                            <i class="fa-solid fa-square-pen"></i>
                        </button>
                        <button type="button" v-on:click="deleteRecord" v-bind:data-pid="food.id" class="btn btn-danger">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <!-- Modal -->
    <div class="modal fade" id="formModal" role="dialog">
        <div class="modal-dialog modal-dialog-scrollable modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div id="main-modal" class="modal-body"></div>
            </div>
        </div>
    </div>
</div>
<?php fig::end() ?>

<?php fig::render() ?>