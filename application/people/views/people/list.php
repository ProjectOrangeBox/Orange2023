<div rv-refresh="refresh.grid" rv-add-show-class="show.grid" class="masthead container" model="<?= getUrl('peopleReadAll') ?>" on-success-property="list">
    <table id="table" class="table table-striped">
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">First</th>
                <th scope="col">Last</th>
                <th scope="col">Age</th>
                <th scope="col">Color</th>
                <th scope="col">
                    <button type="button" rv-on-click="actions.go" model="<?= getUrl('peopleReadNew') ?>" on-success-hide="show.grid" on-success-show="show.create" on-success-property="createRecord" class="btn btn-primary"><i class="fa-solid fa-square-plus"></i></button>
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
                    <button type="button" rv-on-click="actions.go" rv-model="'<?= getUrl('peopleReadOne', ['{1}'], true) ?>' | replace row.id" on-success-show="show.read" on-success-hide="show.grid" on-success-property="readRecord" class="btn btn-primary"><i class="fa-solid fa-eye"></i></button>
                    <button type="button" rv-on-click="actions.go" rv-model="'<?= getUrl('peopleReadOne', ['{1}'], true) ?>' | replace row.id" on-success-show="show.update" on-success-property="updateRecord" class="btn btn-primary"><i class="fa-solid fa-square-pen"></i></button>
                    <button type="button" rv-on-click="actions.go" rv-model="'<?= getUrl('peopleReadOne', ['{1}'], true) ?>' | replace row.id" on-success-show="show.delete" on-success-property="deleteRecord" class="btn btn-danger"><i class="fa-solid fa-trash-can"></i></button>
                </td>
            </tr>
        </tbody>
    </table>
</div>