<?php 

return [
    "tenant" => [
        "clientConfig" => [
            "indexName" => "products"
        ],
        "indexSettingsJson" => '{"number_of_shards": 5, "number_of_replicas": 0}',
        "elasticSearchClientParamsJson" => '{"hosts": ["elasticsearch"]}',
        "generalSearchColumns" => [
            "name" => "searchText"
        ],
        "columns" => [
            [
                "name" => "matnr",
                "json" => '{"type": "string","store": true}'
            ],
            [
                "name" => "ean",
                "json" => '{"type": "string"}'
            ],
            [
                "name" => "sellingFrequency",
                "json" => '{"type": "integer","store": true}'
            ],
            [
                "name" => "shortDescription",
                "json" => '{"type": "string", "store": true}'
            ],
            [
                "name" => "searchText",
                "json" => '{"type": "string"}'
            ],
            [
                "name" => "OSName",
                "json" => '{"type": "string"}'
            ],
            [
                "name" => "Rating",
                "fieldname" => "ESRating",
                "json" => '{"type": "object"}'
            ],
            [
                "name" => "herst",
                "interpreter" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\IndexService\\Interpreter\\DefaultObjects"
            ],
            [
                "name" => "marke",
                "interpreter" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\IndexService\\Interpreter\\DefaultObjects"
            ],
            [
                "name" => "properties",
                "fieldname" => "ESProperties",
                "json" => '{"type": "object", "dynamic": true}'
            ],
            [
                "name" => "prices",
                "fieldname" => "ESPrices",
                "json" => '{"type": "object", "dynamic": true}'
            ],
            [
                "name" => "types",
                "fieldname" => "ESTypes",
                "json" => '{"type": "object", "dynamic": true}'
            ]
        ],
        "filtertypes" => [
            "helper" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\FilterService\\FilterGroupHelper",
            "FilterNumberRange" => [
                "class" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\FilterService\\FilterType\\AbstractFilterType\\ElasticSearch\\NumberRange",
                "script" => "/shop/filter/dump.php"
            ],
            "FilterSelect" => [
                "class" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\FilterService\\FilterType\\AbstractFilterType\\ElasticSearch\\Select",
                "script" => "/shop/filter/dump.php"
            ],
            "FilterMultiSelect" => [
                "class" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\FilterService\\FilterType\\AbstractFilterType\\ElasticSearch\\MultiSelect",
                "script" => "/shop/filter/elasticsearch/multi-select.php"
            ],
            "FilterMultiRelation" => [
                "class" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\FilterService\\FilterType\\AbstractFilterType\\ElasticSearch\\MultiSelectRelation",
                "script" => "/shop/filter/elasticsearch/multi-select-relation.php"
            ],
            "FilterCategory" => [
                "class" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\FilterService\\FilterType\\AbstractFilterType\\ElasticSearch\\SelectCategory",
                "script" => "/shop/filter/elasticsearch/select-category.php"
            ]
        ]
    ]
];
