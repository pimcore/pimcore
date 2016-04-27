<?php

return [
    "views" => [
        [
            "treetype" => "object",
            "name" => "Articles",
            "condition" => NULL,
            "icon" => "/pimcore/static6/img/flat-color-icons/reading.svg",
            "id" => 1,
            "rootfolder" => "/blog",
            "showroot" => FALSE,
            "classes" => "",
            "position" => "right",
            "sort" => "1",
            "expanded" => TRUE,
            "having" => "o_type = \"folder\" || o5.title NOT LIKE '%magnis%'",
            "joins" => [
                array(
                    "type" => "left",
                    "name" => array("o5" => "object_localized_5_en"),
                    "condition" => "objects.o_id = o5.oo_id",
                    "columns" => array("o5" => "title")
                )
            ],
            "where" => ""
        ]
    ]
];
