#!/bin/bash

set -e

function get_composer() {
    wget https://getcomposer.org/composer-1.phar
    chmod a+x composer-1.phar
    mv composer-1.phar /usr/local/bin/composer
}

JOB=$3
COMPOSER_VERSION=$(echo "${JOB}" | jq -r ".composer")

if [[ "${COMPOSER_VERSION}" == "1" ]];then
    get_composer
fi
