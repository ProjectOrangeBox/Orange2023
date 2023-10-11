#!/bin/sh

# --process-isolation

../../../vendor/bin/phpunit --process-isolation --colors --testdox --prepend prepend.php --bootstrap bootstrap.php --testdox-html results.html ../unitTests/