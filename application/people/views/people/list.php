<div rv-refresh="refresh.grid" rv-add-show-class="show.grid" class="masthead container" model="<?= getUrl('peopleReadAll') ?>" property="list">
    <table id="table" class="table table-striped">
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">First</th>
                <th scope="col">Last</th>
                <th scope="col">Age</th>
                <th scope="col">Color</th>
                <th scope="col">
                    <button type="button" rv-on-click="actions.go" hide="show.grid" show="show.create" model="<?= getUrl('peopleReadNew') ?>" property="createRecord" class="btn btn-primary"><i class="fa-solid fa-square-plus"></i></button>
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
                    <button type="button" rv-on-click="actions.go" show="show.read" hide="show.grid" rv-model="'<?= getUrl('peopleReadOne', ['{1}'], true) ?>' | replace row.id" property="readRecord" class="btn btn-primary"><i class="fa-solid fa-eye"></i></button>
                    <button type="button" rv-on-click="actions.go" show="show.update" rv-model="'<?= getUrl('peopleReadOne', ['{1}'], true) ?>' | replace row.id" property="updateRecord" class="btn btn-primary"><i class="fa-solid fa-square-pen"></i></button>
                    <button type="button" rv-on-click="actions.go" show="show.delete" rv-model="'<?= getUrl('peopleReadOne', ['{1}'], true) ?>' | replace row.id" property="deleteRecord" class="btn btn-danger"><i class="fa-solid fa-trash-can"></i></button>
                </td>
            </tr>
        </tbody>
    </table>
</div>