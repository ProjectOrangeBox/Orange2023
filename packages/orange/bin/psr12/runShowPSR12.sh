#!/bin/bash

# quick script to call the code sniffer. By default it will do the whole lib directory

../../../../vendor/squizlabs/php_codesniffer/bin/phpcs --standard=PSR12 --exclude=Generic.Files.LineLength --colors ../../src
