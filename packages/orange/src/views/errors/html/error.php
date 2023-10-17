<?php

$e = function ($c) {
    echo '<p>' . htmlspecialchars($c, ENT_QUOTES, 'UTF-8') . '</p>';
};

foreach ($errors as $error) {
    if (is_array($error)) {
        foreach ($error as $err) {
            $e($err);
        }
    } else {
        $e($error);
    }
}
