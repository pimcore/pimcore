<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->exclude(['views', 'var/config', 'var/classes'])
    ->in([__DIR__ . "/pimcore", __DIR__ . "/website_demo", __DIR__ . "/website_example", __DIR__ . "/tests"])
    //->in([__DIR__ . "/website_demo", __DIR__ . "/website_example", __DIR__ . "/tests"])
;

return Symfony\CS\Config\Config::create()
    ->level(Symfony\CS\FixerInterface::PSR2_LEVEL)
    ->fixers(array('encoding', 'short_tag'))
    ->finder($finder)
    ;
