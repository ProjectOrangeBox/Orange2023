<?php

/**
 * Attach all of the global input to pass them into the handler
 *
 * By doing this it is easier to do unit testing
 *
 */

return [
    'cache directory' => __ROOT__ . '/var/handlebars',
    'forceCompile' => DEBUG,
    'helpers' => [
        __ROOT__ . '/packages/peels/handlebars/src/hbsPlugins/exp:block.hbs.php',
        __ROOT__ . '/packages/peels/handlebars/src/hbsPlugins/exp:channel:entries.hbs.php',
        __ROOT__ . '/packages/peels/handlebars/src/hbsPlugins/exp:query.hbs.php',
        __ROOT__ . '/packages/peels/handlebars/src/hbsPlugins/format:date.hbs.php',
        __ROOT__ . '/packages/peels/handlebars/src/hbsPlugins/hbp:deferred.hbs.php',
        __ROOT__ . '/packages/peels/handlebars/src/hbsPlugins/if_eq.hbs.php',
        __ROOT__ . '/packages/peels/handlebars/src/hbsPlugins/if_gt.hbs.php',
        __ROOT__ . '/packages/peels/handlebars/src/hbsPlugins/if_lt.hbs.php',
        __ROOT__ . '/packages/peels/handlebars/src/hbsPlugins/if_ne.hbs.php',
        __ROOT__ . '/packages/peels/handlebars/src/hbsPlugins/iff.hbs.php',
        __ROOT__ . '/packages/peels/handlebars/src/hbsPlugins/is_even.hbs.php',
        __ROOT__ . '/packages/peels/handlebars/src/hbsPlugins/is_odd.hbs.php',
        __ROOT__ . '/packages/peels/handlebars/src/hbsPlugins/lowercase.hbs.php',
        __ROOT__ . '/packages/peels/handlebars/src/hbsPlugins/now.hbs.php',
        __ROOT__ . '/packages/peels/handlebars/src/hbsPlugins/q:cache_demo.hbs.php',
        __ROOT__ . '/packages/peels/handlebars/src/hbsPlugins/set.hbs.php',
        __ROOT__ . '/packages/peels/handlebars/src/hbsPlugins/uppercase.hbs.php',
    ],
    'template directories' => [
        __ROOT__ . '/packages/peels/handlebars/examples/hb-templates'
    ],
    'template extension' => '.hbs',
    'templates' => [],
    'partials' => [
        'header' => __ROOT__ . '/packages/peels/handlebars/examples/hb-templates/header.hbs',
        'footer' => __ROOT__ . '/packages/peels/handlebars/examples/hb-templates/footer.hbs',
    ],
];
