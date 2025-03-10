<div rv-refresh="refresh.grid" rv-theme-show="show.grid" class="masthead container" model="<?= getUrl('peopleReadAll') ?>" property="list">
    <table id="table" class="table table-striped">
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">First</th>
                <th scope="col">Last</th>
                <th scope="col">Age</th>
                <th scope="col">Color</th>
                <th scope="col">
                    <button type="button" rv-on-click="actions.swap" hide="show.grid" show="show.create" model="<?= getUrl('peopleReadNew') ?>" property="createRecord" class="btn btn-primary"><i class="fa-solid fa-square-plus"></i></button>
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
                    <button type="button" rv-on-click="actions.swap" show="show.read" hide="show.grid" model="<?= getUrl('peopleReadOne', ['{id}'], true) ?>" rv-replace-id="row.id" property="readRecord" class="btn btn-primary"><i class="fa-solid fa-eye"></i></button>
                    <button type="button" rv-on-click="actions.swap" show="show.update" model="<?= getUrl('peopleReadOne', ['{id}'], true) ?>" rv-replace-id="row.id" property="updateRecord" class="btn btn-primary"><i class="fa-solid fa-square-pen"></i></button>
                    <button type="button" rv-on-click="actions.swap" show="show.delete" model="<?= getUrl('peopleReadOne', ['{id}'], true) ?>" rv-replace-id="row.id" property="deleteRecord" class="btn btn-danger"><i class="fa-solid fa-trash-can"></i></button>
                </td>
            </tr>
        </tbody>
    </table>
</div>
<div rv-refresh="watchme">{ watchme }</div>