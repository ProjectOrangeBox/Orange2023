<?php

declare(strict_types=1);

use dmyers\orange\Event;

/*

A closure is also supported:

[function ($router, $input, $output) {
    $output->appendOutput('<p>Copyright '.date('Y').'</p>);
}, Event::PRIORITY_HIGHEST],
*/

return [];

/**
 return [
    'before.router' => [
        [\app\libraries\OutputCors::class . '::handleCrossOriginResourceSharing', Event::PRIORITY_HIGHEST],
        [\app\libraries\Middleware::class . '::beforeRouter'],
    ],
    'before.controller' => [
        [\app\libraries\Middleware::class . '::beforeController'],
    ],
    'after.controller' => [
        [\app\libraries\Middleware::class . '::afterController'],
    ],
    'after.output' => [
        [\app\libraries\Middleware::class . '::afterOutput'],
    ],
    'some.bogus_Event' => [
        ['\app\bogus\class::bogus_method', Event::PRIORITY_LOWEST],
    ],
];
*/
