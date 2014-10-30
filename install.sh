#!/bin/bash

# Copyright (c) 2014 Course Hero, Inc.
# See LICENSE and NOTICE.

# This script bootstraps the development environment needed to work on the
# project.

SCRIPTDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# Download composer
if [ ! -e "${SCRIPTDIR}/composer.phar" ]
then
    # Taken from https://getcomposer.org/download/
    ( cd ${SCRIPTDIR}; php -r "readfile('https://getcomposer.org/installer');" | php )
fi

# Generate composer.lock
( cd ${SCRIPTDIR}; php composer.phar update )

# Run the unit tests
( cd ${SCRIPTDIR}; ./phpunit.sh )
