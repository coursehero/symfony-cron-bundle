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
```yml
services:
    symfony_cron.lock_file_service:
        class: SymfonyCronBundle\Component\Lock\LockFileService
    symfony_cron.default_lock_service: "@symfony_cron.lock_file_service"
    symfony_cron.process_service:
        class: SymfonyCronBundle\Component\Process\ProcessService
```

crontab:
```
* * * * * /path/to/script
```

/path/to/script:
```bash
#!/bin/bash

/usr/bin/php \
    /path/to/symfony/app/console \
    cron:single_exec \
        --id /path/to/lock/files/some-unique-file \
        --child_process \
    -- \
        /path/to/child/script \
        --script-option \
        script-args
```

### Example: Multiple Instances of Same Script as Embedded Application ###

app/config/config.yml:
```yml
services:
    symfony_cron.lock_file_service:
        class: SymfonyCronBundle\Component\Lock\LockFileService
    symfony_cron.default_lock_service: "@symfony_cron.lock_file_service"
    symfony_cron.process_service:
        class: SymfonyCronBundle\Component\Process\ProcessService
```

crontab:
```
* * * * * /path/to/script instance-1-unique-key parameter-set-1
* * * * * /path/to/script instance-2-unique-key parameter-set-2
```

/path/to/script:
```bash
#!/bin/bash

KEY="$1"
PARAM="$2"

/usr/bin/php \
    /path/to/symfony/app/console \
    cron:single_exec \
        --id ${KEY} \
    -- \
        some:command \
        ${PARAM}
```

Contributing
------------

*symfony-cron* is an open source, community-driven project.  If you'd
like to contribute, please read the [Contributing][5] documentation.  If you're
submitting a pull request, please follow the guidelines in the [Submitting a
Patch][5] section and use [Pull Request Template][5].

Running Tests
-------------

All tests are supported using standard [phpunit][6] practices.  A
[helper script][7] has also been provided to assist with running tests.

[1]: http://symfony.com
[2]: http://php.net
[3]: http://getcomposer.org
[4]: https://packagist.org/
[5]: CONTRIBUTING.md
[6]: https://phpunit.de
[7]: phpunit.sh
