<?php fig::extends('templates/base') ?>

<?php fig::block('body') ?>

<?php fig::include('people/list') ?>

<?php fig::include('people/create') ?>
<?php fig::include('people/read') ?>

<?php fig::includeModal('show.update', 'people/modals/update', 'xl') ?>
<?php fig::includeModal('show.delete', 'people/modals/delete', 'sm') ?>
<?php fig::includeModal('validation.show', 'people/modals/validation') ?>

<data rv-refresh="refresh.colordropdown" model="<?= getUrl('peopleColorDropdown') ?>" on-success-property="colorDropDown"></data>

<?php fig::end() ?>

<?php fig::render() ?>