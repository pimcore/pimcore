<?php 

return [
    "general" => [
        "timezone" => "Europe/Berlin",
        "php_cli" => "",
        "domain" => "",
        "redirect_to_maindomain" => "",
        "language" => "en",
        "validLanguages" => "en,de",
        "fallbackLanguages" => [
            "en" => "",
            "de" => ""
        ],
        "theme" => "",
        "contactemail" => "",
        "loginscreencustomimage" => "",
        "disableusagestatistics" => "",
        "debug" => "",
        "debug_ip" => "",
        "http_auth" => [
            "username" => "",
            "password" => ""
        ],
        "custom_php_logfile" => "1",
        "debugloglevel" => "error",
        "debug_admin_translations" => "",
        "devmode" => "",
        "logrecipient" => "",
        "viewSuffix" => "",
        "instanceIdentifier" => "",
        "show_cookie_notice" => "",
        "extjs6" => "1"
    ],
    "database" => [
        "adapter" => "Mysqli",
        "params" => [
            "host" => "localhost",
            "username" => "pimcore_demo",
            "password" => "secretpassword",
            "dbname" => "pimcore_demo_pimcore",
            "port" => "3306"
        ]
    ],
    "documents" => [
        "versions" => [
            "days" => "",
            "steps" => "10"
        ],
        "default_controller" => "default",
        "default_action" => "default",
        "error_pages" => [
            "default" => "/error"
        ],
        "createredirectwhenmoved" => "",
        "allowtrailingslash" => "no",
        "allowcapitals" => "no",
        "generatepreview" => "1",
        "wkhtmltoimage" => "",
        "wkhtmltopdf" => ""
    ],
    "objects" => [
        "versions" => [
            "days" => "",
            "steps" => "10"
        ]
    ],
    "assets" => [
        "webdav" => [
            "hostname" => ""
        ],
        "versions" => [
            "days" => "",
            "steps" => "10"
        ],
        "ffmpeg" => "",
        "ghostscript" => "",
        "libreoffice" => "",
        "icc_rgb_profile" => "",
        "icc_cmyk_profile" => ""
    ],
    "services" => [
        "translate" => [
            "apikey" => ""
        ],
        "google" => [
            "client_id" => "655439141282-tic94n6q3j7ca5c5as132sspeftu5pli.apps.googleusercontent.com",
            "email" => "655439141282-tic94n6q3j7ca5c5as132sspeftu5pli@developer.gserviceaccount.com",
            "simpleapikey" => "AIzaSyCo9Wj49hYJWW2WgOju4iMYNTvdcBxmyQ8",
            "browserapikey" => "AIzaSyBJX16kWAmUVEz1c1amzp2iKqAfumbcoQQ"
        ]
    ],
    "cache" => [
        "enabled" => "",
        "lifetime" => "",
        "excludePatterns" => "",
        "excludeCookie" => ""
    ],
    "outputfilters" => [
        "less" => "",
        "lesscpath" => ""
    ],
    "webservice" => [
        "enabled" => ""
    ],
    "httpclient" => [
        "adapter" => "Zend_Http_Client_Adapter_Socket",
        "proxy_host" => "",
        "proxy_port" => "",
        "proxy_user" => "",
        "proxy_pass" => ""
    ],
    "email" => [
        "sender" => [
            "name" => "pimcore Demo",
            "email" => "pimcore-demo@byom.de"
        ],
        "return" => [
            "name" => "pimcore Demo",
            "email" => "pimcore-demo@byom.de"
        ],
        "method" => "sendmail",
        "smtp" => [
            "host" => "",
            "port" => "",
            "ssl" => "",
            "name" => "",
            "auth" => [
                "method" => "",
                "username" => "",
                "password" => ""
            ]
        ],
        "debug" => [
            "emailaddresses" => "pimcore@byom.de"
        ],
        "bounce" => [
            "type" => "",
            "maildir" => "",
            "mbox" => "",
            "imap" => [
                "host" => "",
                "port" => "",
                "username" => "",
                "password" => "",
                "ssl" => ""
            ]
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
        "method" => "",
        "smtp" => [
            "host" => "",
            "port" => "",
            "ssl" => "",
            "name" => "",
            "auth" => [
                "method" => "",
                "username" => "",
                "password" => ""
            ]
        ],
        "usespecific" => ""
    ]
];
