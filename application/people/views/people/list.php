<?php fig::extends('templates/base') ?>

<?php fig::block('body') ?>

<?php fig::include('people/grid') ?>

<?php fig::include('people/create') ?>
<?php fig::include('people/read') ?>

<?php fig::include('people/modals/update', ['element_id' => 'update', 'size' => 'xl']) ?>
<?php fig::include('people/modals/delete', ['element_id' => 'delete', 'size' => 'sm']) ?>

<?php fig::end() ?>

<?php fig::render() ?>