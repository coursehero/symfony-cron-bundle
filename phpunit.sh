#!/bin/bash

# Copyright (c) 2014 Course Hero, Inc.
# See LICENSE and NOTICE.

# This script runs the copy of phpunit that is installed by composer.
# Command line arguments can be given, which pass through directly to
# phpunit itself.

SCRIPTDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

if [ ! -e "${SCRIPTDIR}/vendor/bin/phpunit" ]
then
    echo "Cannot find phpunit -- have you installed/run composer yet?"
    exit 1
fi

${SCRIPTDIR}/vendor/bin/phpunit $@
