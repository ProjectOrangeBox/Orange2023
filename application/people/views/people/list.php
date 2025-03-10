<?php fig::extends('templates/base') ?>

<?php fig::block('body') ?>

<?php fig::include('people/grid') ?>

<?php fig::include('people/create') ?>
<?php fig::include('people/read') ?>

<?php fig::include('people/modals/update', ['show' => 'show.update']) ?>
<?php fig::include('people/modals/delete', ['show' => 'show.delete']) ?>
<?php fig::include('people/modals/validation', ['show' => 'validation.show']) ?>

<?php fig::end() ?>

<?php fig::render() ?>