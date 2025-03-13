<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title><?= fig::e('h1') ?></title>
    <link rel="icon" type="image/x-icon" href="/assets/favicon.ico" />
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700" rel="stylesheet" type="text/css" />
    <link href="https://fonts.googleapis.com/css?family=Lato:400,700,400italic,700italic" rel="stylesheet" type="text/css" />
    <link href="/assets/css/app.css" rel="stylesheet">
    <?= fig::value('css') ?>
</head>

<body id="app">
    <?= fig::include('partials/nav') ?>
    <?= fig::value('body') ?>
    <script src="/assets/js/app.js"></script>
    <?= fig::value('script') ?>
    <?= fig::value('js') ?>
</body>

</html>