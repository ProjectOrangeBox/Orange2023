<?php

declare(strict_types=1);

/**
 * 
 * Example
 * 
 * return [
 *   'before.router' => [
 *       [\app\libraries\OutputCors::class . '::handleCrossOriginResourceSharing', Event::PRIORITY_HIGHEST],
 *       [\app\libraries\Middleware::class . '::beforeRouter'],
 *   ],
 *   'before.controller' => [
 *       [\app\libraries\Middleware::class . '::beforeController'],
 *   ],
 *   'after.controller' => [
 *       [\app\libraries\Middleware::class . '::afterController'],
 *   ],
 *   'after.output' => [
 *       [\app\libraries\Middleware::class . '::afterOutput'],
 *   ],
 *   'some.bogus_Event' => [
 *       ['\app\bogus\class::bogus_method', Event::PRIORITY_LOWEST],
 *   ],
 * ];
 *
 */

return [];
