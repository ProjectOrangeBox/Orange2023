<style>
    * {
        transition: all 0.6s;
    }

    html {
        height: 100%;
    }

    body {
        font-family: 'Lato', sans-serif;
        color: #888;
        margin: 0;
    }

    #main {
        display: table;
        width: 100%;
        height: 100vh;
        text-align: center;
    }

    .fof {
        display: table-cell;
        vertical-align: middle;
    }

    .fof h1 {
        font-size: 50px;
        display: inline-block;
        padding-right: 12px;
        animation: type .5s alternate infinite;
    }

    pre {
        text-align: left;
        width: 50%;
        margin: 0 auto;
    }

    @keyframes type {
        from {
            box-shadow: inset -3px 0px 0px #888;
        }

        to {
            box-shadow: inset -3px 0px 0px transparent;
        }
    }
</style>
<div id="main">
    <div class="fof">
        
    <h1><?=$title ?? 500 ?></h1>
        <?php
        if (isset($code) && $code > 0) {
            echo '<h2>' . $code . '</h2>';
        }
        if (isset($message)) {
            echo '<h3>' . $message . '</h3>';
        }
        if (isset($file)) {
            echo '<h3>File: ' . $file . '</h3>';
        }
        if (isset($line)) {
            echo '<h3>Line: ' . $line . '</h3>';
        }
        if (!empty($options)) {
            echo '<h3>Context</h3>';
            echo '<pre>' . print_r($options, true) . '</pre>';
        }
        echo '<h4>' . __FILE__ . '</h4>';
        ?>
    </div>
</div>