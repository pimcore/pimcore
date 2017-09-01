<?php

return [
    "general" => [
        "timezone" => "Europe/Berlin",
        "php_cli" => "",
        "domain" => "",
        "redirect_to_maindomain" => FALSE,
        "language" => "en",
        "validLanguages" => "en,de",
        "fallbackLanguages" => [
            "en" => "",
            "de" => ""
        ],
        "theme" => "",
        "contactemail" => "",
        "loginscreencustomimage" => "",
        "disableusagestatistics" => FALSE,
        "debug" => FALSE,
        "debug_ip" => "",
        "http_auth" => [
            "username" => "",
            "password" => ""
        ],
        "debug_admin_translations" => FALSE,
        "devmode" => FALSE,
        "instanceIdentifier" => "",
        "show_cookie_notice" => FALSE
    ],
    "database" => [
        "params" => [
            "host" => "localhost",
            "username" => "pimcore_demo_basic",
            "password" => "secretpassword",
            "dbname" => "pimcore_demo_pimcore_basic",
            "port" => "3306"
        ]
    ],
    "documents" => [
        "versions" => [
            "days" => NULL,
            "steps" => 10
        ],
        "default_controller" => "Default",
        "default_action" => "default",
        "error_pages" => [
            "default" => "/error"
        ],
        "createredirectwhenmoved" => FALSE,
        "allowtrailingslash" => "no",
        "allowcapitals" => "no",
        "generatepreview" => TRUE,
        "wkhtmltoimage" => "",
        "wkhtmltopdf" => ""
    ],
    "objects" => [
        "versions" => [
            "days" => NULL,
            "steps" => 10
        ]
    ],
    "assets" => [
        "versions" => [
            "days" => NULL,
            "steps" => 10
        ],
        "icc_rgb_profile" => "",
        "icc_cmyk_profile" => "",
        "hide_edit_image" => FALSE
    ],
    "services" => [
        "google" => [
            "client_id" => "655439141282-tic94n6q3j7ca5c5as132sspeftu5pli.apps.googleusercontent.com",
            "email" => "655439141282-tic94n6q3j7ca5c5as132sspeftu5pli@developer.gserviceaccount.com",
            "simpleapikey" => "AIzaSyCo9Wj49hYJWW2WgOju4iMYNTvdcBxmyQ8",
            "browserapikey" => "AIzaSyBJX16kWAmUVEz1c1amzp2iKqAfumbcoQQ"
        ]
    ],
    "cache" => [
        "enabled" => FALSE,
        "lifetime" => NULL,
        "excludePatterns" => "",
        "excludeCookie" => ""
    ],
    "outputfilters" => [
        "less" => FALSE,
        "lesscpath" => ""
    ],
    "webservice" => [
        "enabled" => TRUE
    ],
    "httpclient" => [
        "adapter" => "Socket",
        "proxy_host" => "",
        "proxy_port" => "",
        "proxy_user" => "",
        "proxy_pass" => ""
    ],
    "email" => [
        "sender" => [
            "name" => "",
            "email" => ""
        ],
        "return" => [
            "name" => "",
            "email" => ""
        ],
        "method" => "mail",
        "smtp" => [
            "host" => "",
            "port" => "",
            "ssl" => NULL,
            "name" => "",
            "auth" => [
                "method" => NULL,
                "username" => "",
                "password" => ""
            ]
        ],
        "debug" => [
            "emailaddresses" => ""
        ]
    ],
    "newsletter" => [
        "sender" => [
            "name" => "",
            "email" => ""
        ],
        "return" => [
            "name" => "",
            "email" => ""
        ],
        "method" => "mail",
        "smtp" => [
            "host" => "",
            "port" => "",
            "ssl" => NULL,
            "name" => "",
            "auth" => [
                "method" => NULL,
                "username" => "",
                "password" => ""
            ]
        ],
        "usespecific" => ""
    ]
];
