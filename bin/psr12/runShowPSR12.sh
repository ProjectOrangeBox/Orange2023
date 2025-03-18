#!/bin/bash

SCRIPT_DIR="$(dirname "$(realpath "$0")")"
ROOT_DIR=$(realpath -q "$SCRIPT_DIR/../../")
SOURCE=$(realpath -q "$ROOT_DIR/$1")

if ! [ -d "$SOURCE" ]; then
    echo "Please include path starting at the project root $ROOT_DIR"
    exit
fi

echo "Processing $SOURCE"

# quick script to call the code sniffer. By default it will do the whole lib directory

../../vendor/squizlabs/php_codesniffer/bin/phpcs --standard=PSR12 --exclude=Generic.Files.LineLength --colors $SOURCE
