<?php

// PHP-CS-Fixer v 2.0.0

$finder = PhpCsFixer\Finder::create()
    ->exclude(['views', 'var/config', 'var/classes'])
    ->in([__DIR__ . "/pimcore", __DIR__ . "/website_demo", __DIR__ . "/website_example", __DIR__ . "/tests"])
    //->in([__DIR__ . "/tests"])
;

return PhpCsFixer\Config::create()
    ->setRules(array(
        '@PSR2' => true,
        'short_array_syntax' => true,
        "encoding" => true,
        "blank_line_before_return" => true,
        "hash_to_slash_comment" => true,
        "single_blank_line_before_namespace" => true,
        "space_after_semicolon" => true,
        "standardize_not_equals" => true,
        "whitespace_after_comma_in_array" => true,
    ))
    ->finder($finder)
    ;
