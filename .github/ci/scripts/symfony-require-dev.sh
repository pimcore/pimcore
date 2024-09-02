#!/bin/bash

set -eu

composer config minimum-stability "dev"
composer config prefer-stable true
composer require --no-update \
    symfony/cache:${SYMFONY_VERSION} \
    symfony/config:${SYMFONY_VERSION} \
    symfony/console:${SYMFONY_VERSION} \
    symfony/debug-bundle:${SYMFONY_VERSION} \
    symfony/dependency-injection:${SYMFONY_VERSION} \
    symfony/doctrine-bridge:${SYMFONY_VERSION} \
    symfony/doctrine-messenger:${SYMFONY_VERSION} \
    symfony/dom-crawler:${SYMFONY_VERSION} \
    symfony/error-handler:${SYMFONY_VERSION} \
    symfony/event-dispatcher:${SYMFONY_VERSION} \
    symfony/expression-language:${SYMFONY_VERSION} \
    symfony/filesystem:${SYMFONY_VERSION} \
    symfony/finder:${SYMFONY_VERSION} \
    symfony/framework-bundle:${SYMFONY_VERSION} \
    symfony/html-sanitizer:${SYMFONY_VERSION} \
    symfony/http-foundation:${SYMFONY_VERSION} \
    symfony/http-kernel:${SYMFONY_VERSION} \
    symfony/lock:${SYMFONY_VERSION} \
    symfony/mailer:${SYMFONY_VERSION} \
    symfony/messenger:${SYMFONY_VERSION} \
    symfony/mime:${SYMFONY_VERSION} \
    symfony/options-resolver:${SYMFONY_VERSION} \
    symfony/password-hasher:${SYMFONY_VERSION} \
    symfony/process:${SYMFONY_VERSION} \
    symfony/property-access:${SYMFONY_VERSION} \
    symfony/rate-limiter:${SYMFONY_VERSION} \
    symfony/routing:${SYMFONY_VERSION} \
    symfony/security-bundle:${SYMFONY_VERSION} \
    symfony/security-core:${SYMFONY_VERSION} \
    symfony/security-http:${SYMFONY_VERSION} \
    symfony/serializer:${SYMFONY_VERSION} \
    symfony/string:${SYMFONY_VERSION} \
    symfony/templating:${SYMFONY_VERSION} \
    symfony/translation:${SYMFONY_VERSION} \
    symfony/twig-bridge:${SYMFONY_VERSION} \
    symfony/twig-bundle:${SYMFONY_VERSION} \
    symfony/uid:${SYMFONY_VERSION} \
    symfony/validator:${SYMFONY_VERSION} \
    symfony/var-dumper:${SYMFONY_VERSION} \
    symfony/web-profiler-bundle:${SYMFONY_VERSION} \
    symfony/workflow:${SYMFONY_VERSION} \
    symfony/yaml:${SYMFONY_VERSION}
