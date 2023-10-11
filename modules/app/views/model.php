<h1><?= $person->name ?></h1>
<p><b>id</b> <?= $person->id ?></p>
<p><b>age</b> <?= $person->age ?></p>
<p><b>phone</b> <?= $person->phone ?></p>
<hr>
<p><b>Combo</b> <?= $person->combo ?></p>

<?php foreach ($persons as $p) { ?>
    <h1><?= $p->name ?></h1>
    <p><b>id</b> <?= $p->id ?></p>
    <p><b>age</b> <?= $p->age ?></p>
    <p><b>phone</b> <?= $p->phone ?></p>
    <hr>
    <p><b>Combo</b> <?= $p->combo ?></p>
<?php } ?>