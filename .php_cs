<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude(['views', 'var/config', 'var/classes'])
    ->in([__DIR__ . "/pimcore", __DIR__ . "/website_demo", __DIR__ . "/website_example", __DIR__ . "/tests"])
    //->in([__DIR__ . "/website_demo", __DIR__ . "/website_example", __DIR__ . "/tests"])
;

return PhpCsFixer\Config::create()
    ->setRules(array(
        '@PSR2' => true,
        'short_array_syntax' => true,
    ))
    ->fixers(array('encoding', 'short_tag'))
    ->finder($finder)
    ;
