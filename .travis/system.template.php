<?php

return [
    "general"        => [
        "timezone"                 => "Europe/Berlin",
        "path_variable"            => "",
        "domain"                   => "pimcore-test.dev",
        "redirect_to_maindomain"   => false,
        "language"                 => "en",
        "validLanguages"           => "en,de",
        "fallbackLanguages"        => [
            "en" => "",
            "de" => ""
        ],
        "defaultLanguage"          => "",
        "loginscreencustomimage"   => "",
        "disableusagestatistics"   => false,
        "debug"                    => true,
        "debug_ip"                 => "",
        "http_auth"                => [
            "username" => "",
            "password" => ""
        ],
        "debug_admin_translations" => false,
        "devmode"                  => false,
        "instanceIdentifier"       => "",
        "show_cookie_notice"       => false
    ],
    "database"       => [
        "adapter" => "Pdo_Mysql",
        "params"  => [
            "host"     => "localhost",
            "username" => "root",
            "password" => "",
            "dbname"   => "pimcore_test",
            "port"     => "3306"
        ]
    ],
    "documents"      => [
        "versions"                => [
            "days"  => null,
            "steps" => 10
        ],
        "default_controller"      => "Default",
        "default_action"          => "default",
        "error_pages"             => [
            "default" => "/error"
        ],
        "createredirectwhenmoved" => false,
        "allowtrailingslash"      => "no",
        "generatepreview"         => true
    ],
    "objects"        => [
        "versions" => [
            "days"  => null,
            "steps" => 10
        ]
    ],
    "assets"         => [
        "versions"             => [
            "days"  => null,
            "steps" => 10
        ],
        "icc_rgb_profile"      => "",
        "icc_cmyk_profile"     => "",
        "hide_edit_image"      => false,
        "disable_tree_preview" => false
    ],
    "services"       => [
        "google" => [
            "client_id"     => "",
            "email"         => "",
            "simpleapikey"  => "",
            "browserapikey" => ""
        ]
    ],
    "cache"          => [
        "enabled"         => false,
        "lifetime"        => null,
        "excludePatterns" => "",
        "excludeCookie"   => ""
    ],
    "outputfilters"  => [
        "less"      => false,
        "lesscpath" => ""
    ],
    "webservice"     => [
        "enabled" => true
    ],
    "httpclient"     => [
        "adapter"    => "Zend_Http_Client_Adapter_Socket",
        "proxy_host" => "",
        "proxy_port" => "",
        "proxy_user" => "",
        "proxy_pass" => ""
    ],
    "email"          => [
        "sender" => [
            "name"  => "pimcore",
            "email" => "pimcore@example.com"
        ],
        "return" => [
            "name"  => "pimcore",
            "email" => "pimcore@example.com"
        ],
        "method" => "mail",
        "smtp"   => [
            "host" => "",
            "port" => "",
            "ssl"  => null,
            "name" => "",
            "auth" => [
                "method"   => "login",
                "username" => "",
                "password" => ""
            ]
        ],
        "debug"  => [
            "emailaddresses" => ""
        ],
        "bounce" => [
            "type"    => "",
            "maildir" => "",
            "mbox"    => "",
            "imap"    => [
                "host"     => "",
                "port"     => "",
                "username" => "",
                "password" => "",
                "ssl"      => false
            ]
        ]
    ],
    "newsletter"     => [
        "sender"      => [
            "name"  => "",
            "email" => ""
        ],
        "return"      => [
            "name"  => "",
            "email" => ""
        ],
        "method"      => null,
        "smtp"        => [
            "host" => "",
            "port" => "",
            "ssl"  => "ssl",
            "name" => "",
            "auth" => [
                "method"   => null,
                "username" => "",
                "password" => null
            ]
        ],
        "debug"       => null,
        "usespecific" => true
    ],
    "applicationlog" => [
        "mail_notification"            => [
            "send_log_summary" => false,
            "filter_priority"  => null,
            "mail_receiver"    => ""
        ],
        "archive_treshold"             => "30",
        "archive_alternative_database" => ""
    ]
];
