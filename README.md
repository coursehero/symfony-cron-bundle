README
======

What is symfony-cron?
---------------------

*symfony-cron* is a bundle of utilities for [Symfony2][1] that provide
functions related to scheduled tasks run out of a cron context.

Requirements
------------

*symfony-cron* is supported on [PHP 5.4+][2] with [Symfony 2.5+][1].

Installation
------------

The best way to install *symfony-cron* is to include the bundle using a
mechanism such as [composer][3].  Information about the package is
published to [packagist][4].

Use
---

To use *symfony-cron* in a Symfony2-based project, start by defining how
you want to use the system.  The following checklist might help:

* What type of lock service will be used?  (File, memcached, etc.)
* Do commands need to be executed in a child process?
  - Processes that unexpectedly terminate cause locks to be unreleased
    if not executed in a child process, thus preventing future processes
    from running.

### Example: File Lock with Child Process ###

app/config/config.yml:

    services:
        symfony_cron.lock_file_service:
            class: SymfonyCron\Component\Lock\LockFileService
        symfony_cron.default_lock_service: "@symfony_cron.lock_file_service"
        symfony_cron.process_service:
            class: SymfonyCron\Component\Process\ProcessService

crontab:

    * * * * * /path/to/script

/path/to/script:

    #!/bin/bash

    /usr/bin/php \
        /path/to/symfony/app/console \
        cron:single_exec \
            --id /path/to/lock/files/some-unique-file \
            --child_process \
            /path/to/child/script

Contributing
------------

*symfony-cron* is an open source, community-driven project.  If you'd
like to contribute, please read the [Contributing Code][5] part of the
documentation.  If you're submitting a pull request, please follow the
guidelines in the [Submitting a Patch][6] section and use [Pull Request
Template][7].

Running Tests
-------------

All tests are supported using standard phpunit practices.

[1]: http://symfony.com
[2]: http://php.net
[3]: http://getcomposer.org
[4]: https://packagist.org/
[5]: http://to-be-determined
[6]: http://to-be-determined
[7]: http://to-be-determined
