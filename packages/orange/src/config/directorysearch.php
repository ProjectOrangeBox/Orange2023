<?php

declare(strict_types=1);

use orange\framework\interfaces\DirectorySearchInterface;

// defaults
return [
    'match' => '*.php', // glob format
    'quiet' => false, // throw exceptions when resource not found?
    'normalize keys' => true,
    'hash keys' => false, // if your keys are large is it helpful to hash them instead
    'recursive' => false, // recursive search directories
    'locked' => false, // does it start locked?
    'lock after scan' => false, // lock after first scan (read)
    'pend' => DirectorySearchInterface::PREPEND, // append or prepend new directories to search list?
    'callback' => [], // class::method
    'resource key style' => 'view', // can also be a custom closure
    'directories' => [], // startup defaults
    'resources' => [],
];
