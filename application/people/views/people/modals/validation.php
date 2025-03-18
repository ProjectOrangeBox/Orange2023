<div>
    <div class="mb-3 content">
        <div rv-each-row="validation.array">
            <i class="fa-solid fa-triangle-exclamation text-danger"></i> {row.text}
        </div>
    </div>
    <div class="mb-3 float-end">
        <a rv-on-click="actions.go" hide="validation.show" class="btn btn-primary">Close</a>
    </div>
</div>