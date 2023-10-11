<?php

// this bootstrap file is loaded before the application is started
// see htdocs/index.php

if (file_exists(__ROOT__.'/packages/peel/fig/src/fig.php')) {
    require __ROOT__.'/packages/peel/fig/src/fig.php';

    fig::addPath(__ROOT__.'/packages/peel/fig/src/figs');
}

if (file_exists(__ROOT__.'/modules/people/helpers/sendValidationErrors.php')) {
    require __ROOT__ . '/modules/people/helpers/sendValidationErrors.php';
}
