<?php

declare(strict_types=1);

use orange\framework\Event;

/**
 * return [
 *     'before.controller' => [
 *         [[\application\shared\libraries\permission::class, 'beforeController'], Event::PRIORITY_HIGHEST],
 *     ],
 *     'before.output' => [
 *         [[\application\shared\libraries\write::class, 'afterController'], Event::PRIORITY_HIGHEST],
 *     ]
 * ];
 */

return [
    'before.controller' => [
        [[\application\shared\libraries\permission::class, 'beforeController'], Event::PRIORITY_HIGHEST],
    ],
    'before.output' => [
        [[\application\shared\libraries\write::class, 'afterController'], Event::PRIORITY_HIGHEST],
    ]
];
