<?php fig::include('partials/modal_header', ['element_id' => $element_id ?? '', 'size' => $size ?? '']) ?>
<div>
    <div class="mb-3 content">
        <div rv-each-row="validations">
            <i class="fa-solid fa-triangle-exclamation"></i> {row.text}
        </div>
    </div>
    <div class="mb-3 float-end">
        <a rv-on-click="actions.go" hide="show.validate" class="btn btn-primary">Close</a>
    </div>
</div>
<?php fig::include('partials/modal_footer') ?>