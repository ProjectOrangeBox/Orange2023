#!/bin/sh
# if they pass a specific test file name (without the .php) then run just that filename
if [ -n "$1" ]; then
    APPEND="/$1.php"
else
    APPEND=""
fi
# --process-isolation
# --debug

../../../../vendor/bin/phpunit --process-isolation --colors --testdox --bootstrap bootstrap.php --stop-on-defect --fail-on-warning --testdox-text results.txt --testdox-html results.html ../../tests$APPEND