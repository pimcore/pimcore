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
        "controller" => "category_example",
        "action" => "test",
        "variables" => NULL,
        "defaults" => NULL,
        "siteId" => NULL,
        "priority" => "1",
        "creationDate" => "1419933908",
        "modificationDate" => "1419933931",
        "id" => "3"
    ]
];