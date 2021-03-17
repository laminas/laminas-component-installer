#!/bin/bash

set -e

function get_composer() {
    wget https://getcomposer.org/composer-1.phar
    chmod a+x composer-1.phar
    mv composer-1.phar /usr/local/bin/composer-1
}

JOB=$3
COMMAND=$(echo "${JOB}" | jq -r ".command")
PATTERN="composer-1 install"

if [[ "${COMMAND}" =~ ${PATTERN} ]];then
    get_composer
fi
