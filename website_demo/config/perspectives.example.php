<?php

return [
    "default" => [
        "iconCls" => "pimcore_icon_perspective",
        "elementTree" => [
            [
                "name" => "view1",
                "hidden" => true
            ],
            [
                "type" => "documents",
                "position" => "left",
                "expanded" => false,
                "hidden" => false,
                "sort" => -3
            ],
            [
                "type" => "assets",
                "position" => "left",
                "expanded" => false,
                "hidden" => false,
                "sort" => -2
            ],
            [
                "type" => "objects",
                "position" => "left",
                "expanded" => false,
                "hidden" => false,
                "sort" => -1
            ],
        ],
        "dashboards" => [
            "welcome" => array(
                "positions" => array(
                    array(
                        array(
                            "id" => 1,
                            "type" => "pimcore.layout.portlets.modificationStatistic",
                            "config" => null
                        )
                    ),
                    array(
                        array(
                            "id" => 3,
                            "type" => "pimcore.layout.portlets.modifiedObjects",
                            "config" => null
                        ),
                        array(
                            "id" => 4,
                            "type" => "pimcore.layout.portlets.modifiedDocuments",
                            "config" => null
                        )
                    )
                )
            )
        ]
    ],
    "first alternative" => [
        "icon" => "/pimcore/static6/img/flat-color-icons/biohazard.svg",
        "toolbar" => [
            "file" => [
                "hidden" => false,
                "items" => [
                    "dashboards" => 0
// Is the same as
//                    "dashboards" => [
//                        "hidden" => true
//                    ]
                    ,
                    "openAsset" => false

                ]
            ],
            "extras" => [
                "hidden" => false,
                "items" => [
                    "systemtools" => [
                        "items" => [
                            "fileexplorer" => false
                        ]
                    ],
                    "update" => false,
                    "maintenance" => false
                ]

            ],
            "marketing" => [
                "hidden" => 1
            ],
            "settings" => [
                "items" => [
                    "cache" => [
                        "items" => [
                            "clearAll" => 0
                        ]
                    ]
                ]
            ],
            "search" => [
                "items" => [
                    "objects" => false
                ]
            ]
        ],
        "elementTree" => [
            [
                "type" => "documents",
                "position" => "left",
                "expanded" => false,
                "hidden" => false,
                "sort" => 3
            ],
            [
                "type" => "assets",
                "position" => "right",
                "expanded" => true,
                "hidden" => false,
                "sort" => -2
            ],
            [
                "type" => "objects",
                "position" => "left",
                "expanded" => false,
                "hidden" => false,
                "sort" => -1
            ],
        ]
    ],
    "SECOND alternative" => [
        "icon" => "/pimcore/static6/img/flat-color-icons/low_battery.svg",
        "extjsDev" => true,
        "nicename" => "second nice name",
        "elementTree" => [
            [
                "type" => "documents",
                "position" => "right",
                "expanded" => false,
                "hidden" => false,
                "sort" => 15
            ],
            [
                "type" => "assets",
                "position" => "right",
                "expanded" => true,
                "hidden" => false,
                "sort" => -1
            ],
            [
                "type" => "objects",
                "position" => "left",
                "expanded" => false,
                "hidden" => true,
                "sort" => -2
            ],
            [
                "type" => "customview",
                "name" => "view1",
                "hidden" => 1
            ],
            "toolbar" => [
                "search" => 0
            ]
        ]

    ]
];
