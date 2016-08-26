<?php

return [
    [
        "name" => "name",
        "type" => "varchar(255)",
        "locale" => "en_GB",
        "filtergroup" => "string"
    ],
    [
        "name" => "seoname",
        "type" => "varchar(255)",
        "filtergroup" => "string"
    ],
    [
        "name" => "color",
        "type" => "varchar(255)",
        "filtergroup" => "multiselect"
    ],
    [
        "language" => "%locale%",
        "name" => "region",
        "type" => "varchar(255)",
        "filtergroup" => "multiselect"
    ]
];