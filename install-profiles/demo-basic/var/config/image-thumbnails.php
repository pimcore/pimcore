<?php

return [
    "content" => [
        "items" => [
            [
                "method" => "scaleByWidth",
                "arguments" => [
                    "width" => "870"
                ]
            ]
        ],
        "medias" => [

        ],
        "description" => "",
        "format" => "SOURCE",
        "quality" => 95,
        "highResolution" => 0,
        "filenameSuffix" => NULL,
        "id" => "content"
    ],
    "exampleCombined1" => [
        "items" => [
            [
                "method" => "scaleByWidth",
                "arguments" => [
                    "width" => "275"
                ]
            ],
            [
                "method" => "roundCorners",
                "arguments" => [
                    "width" => "10",
                    "height" => "10"
                ]
            ],
            [
                "method" => "rotate",
                "arguments" => [
                    "angle" => "10"
                ]
            ],
            [
                "method" => "addOverlay",
                "arguments" => [
                    "path" => "/web/static/img/logo-overlay.png",
                    "x" => "10",
                    "y" => "10",
                    "origin" => "bottom-right",
                    "alpha" => "100",
                    "composite" => "COMPOSITE_DEFAULT"
                ]
            ]
        ],
        "medias" => [

        ],
        "description" => "",
        "format" => "SOURCE",
        "quality" => 90,
        "highResolution" => 0,
        "filenameSuffix" => NULL,
        "id" => "exampleCombined1"
    ],
    "exampleCombined2" => [
        "items" => [
            [
                "method" => "frame",
                "arguments" => [
                    "width" => "275",
                    "height" => "150"
                ]
            ],
            [
                "method" => "grayscale",
                "arguments" => ""
            ],
            [
                "method" => "setBackgroundColor",
                "arguments" => [
                    "color" => "#ff6600"
                ]
            ]
        ],
        "medias" => [

        ],
        "description" => "",
        "format" => "SOURCE",
        "quality" => 90,
        "highResolution" => 0,
        "filenameSuffix" => NULL,
        "id" => "exampleCombined2"
    ],
    "exampleContain" => [
        "items" => [
            [
                "method" => "contain",
                "arguments" => [
                    "width" => "275",
                    "height" => "150"
                ]
            ]
        ],
        "medias" => [

        ],
        "description" => "",
        "format" => "SOURCE",
        "quality" => 90,
        "highResolution" => 0,
        "filenameSuffix" => NULL,
        "id" => "exampleContain"
    ],
    "exampleCorners" => [
        "items" => [
            [
                "method" => "cover",
                "arguments" => [
                    "width" => "275",
                    "height" => "150",
                    "positioning" => "center",
                    "doNotScaleUp" => "1"
                ]
            ],
            [
                "method" => "addOverlay",
                "arguments" => [
                    "path" => "/web/static/img/logo-overlay.png",
                    "x" => "10",
                    "y" => "10",
                    "origin" => "top-left",
                    "alpha" => "100",
                    "composite" => "COMPOSITE_DEFAULT"
                ]
            ],
            [
                "method" => "roundCorners",
                "arguments" => [
                    "width" => "10",
                    "height" => "10"
                ]
            ]
        ],
        "medias" => [

        ],
        "description" => "",
        "format" => "SOURCE",
        "quality" => 90,
        "highResolution" => 0,
        "filenameSuffix" => NULL,
        "id" => "exampleCorners"
    ],
    "exampleCover" => [
        "items" => [
            [
                "method" => "cover",
                "arguments" => [
                    "width" => "275",
                    "height" => "150",
                    "positioning" => "center",
                    "doNotScaleUp" => "1"
                ]
            ]
        ],
        "medias" => [

        ],
        "description" => "",
        "format" => "SOURCE",
        "quality" => 90,
        "highResolution" => 0,
        "filenameSuffix" => NULL,
        "id" => "exampleCover"
    ],
    "exampleFrame" => [
        "items" => [
            [
                "method" => "frame",
                "arguments" => [
                    "width" => "275",
                    "height" => "150"
                ]
            ]
        ],
        "medias" => [

        ],
        "description" => "",
        "format" => "SOURCE",
        "quality" => 90,
        "highResolution" => 0,
        "filenameSuffix" => NULL,
        "id" => "exampleFrame"
    ],
    "exampleGrayscale" => [
        "items" => [
            [
                "method" => "frame",
                "arguments" => [
                    "width" => "275",
                    "height" => "150"
                ]
            ],
            [
                "method" => "grayscale",
                "arguments" => ""
            ]
        ],
        "medias" => [

        ],
        "description" => "",
        "format" => "SOURCE",
        "quality" => 90,
        "highResolution" => 0,
        "filenameSuffix" => NULL,
        "id" => "exampleGrayscale"
    ],
    "exampleMask" => [
        "items" => [
            [
                "method" => "cover",
                "arguments" => [
                    "width" => "275",
                    "height" => "150",
                    "positioning" => "center",
                    "doNotScaleUp" => "1"
                ]
            ],
            [
                "method" => "applyMask",
                "arguments" => [
                    "path" => "/web/static/img/mask-example.png"
                ]
            ]
        ],
        "medias" => [

        ],
        "description" => "",
        "format" => "SOURCE",
        "quality" => 90,
        "highResolution" => 0,
        "filenameSuffix" => NULL,
        "id" => "exampleMask"
    ],
    "exampleOverlay" => [
        "items" => [
            [
                "method" => "cover",
                "arguments" => [
                    "width" => "275",
                    "height" => "150",
                    "positioning" => "centerleft",
                    "doNotScaleUp" => "1"
                ]
            ],
            [
                "method" => "addOverlay",
                "arguments" => [
                    "path" => "/web/static/img/logo-overlay.png",
                    "x" => "10",
                    "y" => "10",
                    "origin" => "top-left",
                    "alpha" => "75",
                    "composite" => "COMPOSITE_DEFAULT"
                ]
            ]
        ],
        "medias" => [

        ],
        "description" => "",
        "format" => "SOURCE",
        "quality" => 90,
        "highResolution" => 0,
        "filenameSuffix" => NULL,
        "id" => "exampleOverlay"
    ],
    "exampleResize" => [
        "items" => [
            [
                "method" => "resize",
                "arguments" => [
                    "width" => "275",
                    "height" => "150"
                ]
            ]
        ],
        "medias" => [

        ],
        "description" => "",
        "format" => "SOURCE",
        "quality" => 90,
        "highResolution" => 0,
        "filenameSuffix" => NULL,
        "id" => "exampleResize"
    ],
    "exampleRotate" => [
        "items" => [
            [
                "method" => "scaleByWidth",
                "arguments" => [
                    "width" => "275"
                ]
            ],
            [
                "method" => "rotate",
                "arguments" => [
                    "angle" => "5"
                ]
            ]
        ],
        "medias" => [

        ],
        "description" => "",
        "format" => "SOURCE",
        "quality" => 90,
        "highResolution" => 0,
        "filenameSuffix" => NULL,
        "id" => "exampleRotate"
    ],
    "exampleScaleHeight" => [
        "items" => [
            [
                "method" => "scaleByHeight",
                "arguments" => [
                    "height" => "150"
                ]
            ]
        ],
        "medias" => [

        ],
        "description" => "",
        "format" => "SOURCE",
        "quality" => 90,
        "highResolution" => 0,
        "filenameSuffix" => NULL,
        "id" => "exampleScaleHeight"
    ],
    "exampleScaleWidth" => [
        "items" => [
            [
                "method" => "scaleByWidth",
                "arguments" => [
                    "width" => "275"
                ]
            ]
        ],
        "medias" => [

        ],
        "description" => "",
        "format" => "SOURCE",
        "quality" => 90,
        "highResolution" => 0,
        "filenameSuffix" => NULL,
        "id" => "exampleScaleWidth"
    ],
    "exampleSepia" => [
        "items" => [
            [
                "method" => "scaleByWidth",
                "arguments" => [
                    "width" => "275"
                ]
            ],
            [
                "method" => "sepia",
                "arguments" => ""
            ]
        ],
        "medias" => [

        ],
        "description" => "",
        "format" => "SOURCE",
        "quality" => 90,
        "highResolution" => 0,
        "filenameSuffix" => NULL,
        "id" => "exampleSepia"
    ],
    "featurerette" => [
        "items" => [
            [
                "method" => "cover",
                "arguments" => [
                    "width" => "512",
                    "height" => "260",
                    "positioning" => "center",
                    "doNotScaleUp" => "1"
                ]
            ]
        ],
        "medias" => [

        ],
        "description" => "",
        "format" => "SOURCE",
        "quality" => 85,
        "highResolution" => 0,
        "filenameSuffix" => NULL,
        "id" => "featurerette"
    ],
    "galleryCarousel" => [
        "items" => [
            [
                "method" => "cover",
                "arguments" => [
                    "width" => "1140",
                    "height" => "400",
                    "positioning" => "center",
                    "doNotScaleUp" => "1"
                ]
            ]
        ],
        "medias" => [
            "940w" => [
                [
                    "method" => "cover",
                    "arguments" => [
                        "width" => "940",
                        "height" => "350",
                        "positioning" => "center"
                    ]
                ]
            ],
            "720w" => [
                [
                    "method" => "cover",
                    "arguments" => [
                        "width" => "720",
                        "height" => "300",
                        "positioning" => "center"
                    ]
                ]
            ],
            "320w" => [
                [
                    "method" => "cover",
                    "arguments" => [
                        "width" => "320",
                        "height" => "100",
                        "positioning" => "center"
                    ]
                ]
            ]
        ],
        "description" => "",
        "format" => "SOURCE",
        "quality" => 90,
        "highResolution" => 0,
        "filenameSuffix" => NULL,
        "id" => "galleryCarousel"
    ],
    "galleryCarouselPreview" => [
        "items" => [
            [
                "method" => "cover",
                "arguments" => [
                    "width" => "100",
                    "height" => "54",
                    "positioning" => "center",
                    "doNotScaleUp" => "1"
                ]
            ]
        ],
        "medias" => [

        ],
        "description" => "",
        "format" => "SOURCE",
        "quality" => 90,
        "highResolution" => 0,
        "filenameSuffix" => NULL,
        "id" => "galleryCarouselPreview"
    ],
    "galleryLightbox" => [
        "items" => [
            [
                "method" => "scaleByWidth",
                "arguments" => [
                    "width" => "900"
                ]
            ]
        ],
        "medias" => [

        ],
        "description" => "",
        "format" => "SOURCE",
        "quality" => 75,
        "highResolution" => 0,
        "filenameSuffix" => NULL,
        "id" => "galleryLightbox"
    ],
    "galleryThumbnail" => [
        "items" => [
            [
                "method" => "cover",
                "arguments" => [
                    "width" => "260",
                    "height" => "180",
                    "positioning" => "center",
                    "doNotScaleUp" => "1"
                ]
            ]
        ],
        "medias" => [

        ],
        "description" => "",
        "format" => "SOURCE",
        "quality" => 90,
        "highResolution" => 0,
        "filenameSuffix" => NULL,
        "id" => "galleryThumbnail"
    ],
    "newsList" => [
        "items" => [
            [
                "method" => "cover",
                "arguments" => [
                    "width" => "80",
                    "height" => "80",
                    "positioning" => "center",
                    "doNotScaleUp" => "1"
                ]
            ]
        ],
        "medias" => [

        ],
        "description" => "",
        "format" => "SOURCE",
        "quality" => 90,
        "highResolution" => 0,
        "filenameSuffix" => NULL,
        "id" => "newsList"
    ],
    "portalCarousel" => [
        "items" => [
            [
                "method" => "scaleByWidth",
                "arguments" => [
                    "width" => "1500"
                ]
            ]
        ],
        "medias" => [

        ],
        "description" => "",
        "format" => "SOURCE",
        "quality" => 90,
        "highResolution" => 0,
        "filenameSuffix" => "",
        "id" => "portalCarousel"
    ],
    "standardTeaser" => [
        "items" => [
            [
                "method" => "cover",
                "arguments" => [
                    "width" => "360",
                    "height" => "200",
                    "positioning" => "center",
                    "doNotScaleUp" => "1"
                ]
            ]
        ],
        "medias" => [

        ],
        "description" => "",
        "format" => "SOURCE",
        "quality" => 90,
        "highResolution" => 0,
        "filenameSuffix" => NULL,
        "id" => "standardTeaser"
    ]
];
