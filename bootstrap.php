<?php

// this bootstrap file is loaded before the application is started
// see htdocs/index.php

if (file_exists(__ROOT__.'/packages/peels/fig/src/fig.php')) {
    require __ROOT__.'/packages/peels/fig/src/fig.php';

    fig::addPath(__ROOT__.'/packages/peels/fig/src/figs');
}
