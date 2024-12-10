#!/bin/sh
# if they pass a specific test file name (without the .php) then run just that filename
if [ -n "$1" ]; then
    APPEND="/$1.php"
else
    APPEND=""
fi
# --process-isolation

../../../../../vendor/bin/phpunit --process-isolation --colors --testdox --prepend prepend.php --bootstrap bootstrap.php --testdox-html results.html ../../unitTests$APPEND