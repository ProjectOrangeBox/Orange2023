<?php

function useFig()
{
    static $alreadyCalled;

    if ($alreadyCalled !== true) {
        require_once __DIR__ . '/fig.php';

        fig::addPath(__DIR__ . '/figs');

        if (container()->config->configSearch->exists('fig')) {
            $config = container()->config->get('fig');

            if (isset($config['plugins directories']) && is_array($config['plugins directories'])) {
                fig::addPaths($config['plugins directories']);
            }
        }

        $alreadyCalled = true;
    }
}
