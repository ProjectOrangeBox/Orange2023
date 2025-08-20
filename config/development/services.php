<?php

declare(strict_types=1);

use orange\framework\Tester;

return [
    // closure
    'uid' => fn() => bin2hex(random_bytes(16)),
];
