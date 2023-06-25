<?php

if (isset($errors) && is_array($errors)) {
    foreach ($errors as $index => $error) {
        echo '<p>' . $error . '</p>';
    }
}
?>
<h1><?= $heading ?></h1>
<pre><?= $message ?></pre>