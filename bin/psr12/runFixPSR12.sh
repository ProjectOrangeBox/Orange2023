#!/bin/bash

if ! [ -d "$1" ]; then
    echo "Please include relative path."
    exit
fi

# quick script to call the code sniffer. By default it will do the whole lib directory

../../vendor/squizlabs/php_codesniffer/bin/phpcbf --standard=PSR12 --exclude=Generic.Files.LineLength --colors $1
