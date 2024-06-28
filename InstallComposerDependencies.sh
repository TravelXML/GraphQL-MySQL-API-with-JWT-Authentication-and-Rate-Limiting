#!/bin/sh
if [ -f composer.lock ]; then
    composer install
else
    composer install
fi
