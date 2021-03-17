#!/bin/bash

set -e

function get_composer() {
    wget https://getcomposer.org/composer-1.phar
    chmod a+x composer-1.phar
    mv composer-1.phar /usr/local/bin/composer
}

JOB=$2
COMPOSER_VERSION=$(echo "${JOB}" | jq -r ".composer")

if [[ "${COMPOSER_VERSION}" == "1" ]];then
    get_composer
    rm -rf ./vendor
    # No need to vary based on dependency set, as we already did; at this point,
    # we just install what's in the lockfile.
    composer install --ansi --no-interaction --no-progress --prefer-dist
fi
