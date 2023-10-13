<?php
foreach ($errors as $error) {
    if (is_array($error)) {
        foreach ($error as $err) {
            echo $err.PHP_EOL;
        }
    } else {
        echo $error.PHP_EOL;
    }
}
?>