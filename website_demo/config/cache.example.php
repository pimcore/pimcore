<?php

return [
    "frontend" => [
        "type" => "Core",
        "options" => [
            "cache_id_prefix" => "ax_",
            "lifetime" => "99999",
            "automatic_serialization" => "true"
        ]
    ],
    "backend" => [
        "type" => "\\Pimcore\\Cache\\Backend\\Redis2",
        "custom" => "true",
        "options" => [
            "server" => "127.0.0.1",
            "port" => "6379",
            "persistent" => "1",
            "database" => "8",
            "use_lua" => "1"
        ]
    ]
];
