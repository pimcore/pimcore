<?php

return [
    1 => [
        "name" => "news",
        "pattern" => "/(.*)_n([\\d]+)/",
        "reverse" => "%prefix/%text_n%id",
        "module" => "",
        "controller" => "news",
        "action" => "detail",
        "variables" => "text,id",
        "defaults" => "",
        "siteId" => "0",
        "priority" => "1",
        "creationDate" => "0",
        "modificationDate" => "0",
        "id" => "1"
    ],
    2 => [
        "name" => "blog",
        "pattern" => "/(.*)_b([\\d]+)/",
        "reverse" => "%prefix/%text_b%id",
        "module" => "",
        "controller" => "blog",
        "action" => "detail",
        "variables" => "text,id",
        "defaults" => "",
        "siteId" => "0",
        "priority" => "1",
        "creationDate" => "1388391249",
        "modificationDate" => "1388391368",
        "id" => "2"
    ],
    3 => [
        "name" => "category-example",
        "pattern" => "@/category\\-example@",
        "reverse" => "/en/category-example",
        "module" => "",
        "controller" => "Category\\Example",
        "action" => "test",
        "variables" => NULL,
        "defaults" => NULL,
        "siteId" => NULL,
        "priority" => "1",
        "creationDate" => "1419933908",
        "modificationDate" => "1419933931",
        "id" => "3"
    ],
    4 => [
        "id" => 4,
        "name" => "demo_login",
        "pattern" => "@^/(de|en)/secure/login\$@",
        "reverse" => "/%_locale/secure/login",
        "module" => "AppBundle",
        "controller" => "Secure",
        "action" => "login",
        "variables" => "_locale",
        "defaults" => "_locale=en",
        "siteId" => [
        ],
        "priority" => 0,
        "legacy" => FALSE,
        "creationDate" => 1490874634,
        "modificationDate" => 1490874753
    ],
    5 => [
        "id" => 5,
        "name" => "demo_logout",
        "pattern" => "@^/(de|en)/secure/logout\$@",
        "reverse" => "/%_locale/secure/logout",
        "module" => "AppBundle",
        "controller" => "Secure",
        "action" => "logout",
        "variables" => "_locale",
        "defaults" => "_locale=en",
        "siteId" => [

        ],
        "priority" => 0,
        "legacy" => FALSE,
        "creationDate" => 1490874774,
        "modificationDate" => 1490874774
    ],
];
