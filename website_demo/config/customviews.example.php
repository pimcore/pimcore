<?php

return [
    "views" => [
        [
            "treetype" => "object",                                             // element type is "object"
            "name" => "Articles",                                               // display name
            "condition" => null,                                                // SQL condition
            "icon" => "/pimcore/static6/img/flat-color-icons/reading.svg",      // tree icon
            "id" => 1,                                                          // unique (!!!) custom view ID
            "rootfolder" => "/blog",                                            // root node
            "showroot" => false,                                                // show root node or just children?
            "classes" => "",                                                    // allowed classes to add; use class ids; comma-separated
            "position" => "right",                                              // left or right accordion
            "sort" => "1",                                                      // sort priority. lower values are shown first (prio for standard trees is -3 docs,-2 assets,-1 objects)
            "expanded" => true,                                                 // tree is expanded by default (there can be only one expanded tree on each side)
            "having" => "o_type = \"folder\" || o5.title NOT LIKE '%magnis%'",  // SQL having clause
            "joins" => [                                                        // Joins in Zend_DB_Select-like syntax
                [
                    "type" => "left",
                    "name" => ["o5" => "object_localized_5_en"],
                    "condition" => "objects.o_id = o5.oo_id",
                    "columns" => ["o5" => "title"]
                ]
            ],
            "where" => "",
            "treeContextMenu" => [
                "object" => [
                    "items" => [
                        "add" => 0,                                             // hide "Add Object" , "Add Variant"
                        "addFolder" => 0,                                       // hide "Add Folder"
                        "importCsv" => 0,                                       // hide "Import From CSV"
                        "paste" => 0,                                           // hide "Paste"
                        "copy" => 0,                                            // hide "Copy"
                        "cut" => 0,                                             // hide "Cut"
                        "publish" => 0,                                         // hide "Publish"
                        "unpublish" => 0,                                       // hide "Unpublish"
                        "delete" => 1,                                          // show "Delete" (redundant as this is the default)
                        "rename" => 0,                                          // hide "Rename"
                        "searchAndMove" => 0,                                   // hide "Search And Move"
                        "lock" => 0,                                            // hide "Lock"
                        "unlock" => 0,                                          // hide "Unlock"
                        "lockAndPropagate" => 0,                                // hide "Lock and Propagate"
                        "unlockAndPropagete" => 0,                              // hide "Unlock and Propagate"
                        "reload" => 0                                           // hide reload
                    ]
                ]
            ]
        ],
        [
            "treetype" => "document",                                           // document view
            "name" => "Basic Examples",
            "icon" => "/pimcore/static6/img/flat-color-icons/text.svg",
            "id" => 2,                                                          // again, unique ID
            "rootfolder" => "/en/basic-examples",
            "showroot" => true,
            "position" => "right",                                              // show it in the right accordion
            "sort" => "-10",
            "expanded" => true,                                                 // expand the tree panel
            "treeContextMenu" => [
                "document" => [
                    "items" => [
                        "add" => 0,                                             // hide all the "Add *" stuff
                        "addFolder" => 0,                                       // hide "Add Folder"
                        "paste" => 0,                                           // hide all the "Paste" options
                        "pasteCut" => 0,                                        // hide "Paste Cut element"
                        "copy" => 0,                                            // hide "Copy"
                        "cut" => 0,                                             // hide "Cut"
                        "rename" => 0,                                          // hide "Rename"
                        "unpublish" => 0,                                       // hide "Unpublish"
                        "publish" => 0,                                         // hide "Publish"
                        "delete" => 0,                                          // hide "Delete"
                        "open" => 0,                                            // hide "Open"
                        "convert" => 0,                                         // hide "Convert"
                        "searchAndMove" => 0,                                   // hide "Search And Move"
                        "useAsSite" => 0,                                       // hide "Use As Site"
                        "editSite" => 0,                                        // hide "Edit Site"
                        "removeSite" => 0,                                      // hide "Remove Site"
                        "lock" => 0,                                            // hide "Lock"
                        "unlock" => 0,                                          // hide "Unlock"
                        "lockAndPropagate" => 0,                                // hide "UnlockAndPropagate"
                        "unlockAndPropagate" => 0,                              // hide "Lock And Propagate"
                        "reload" => 1                                           // show "Reload" (redundant, visible by default anyway)
                    ]
                ]
            ]
        ],
        [
            "treetype" => "asset",                                              // asset view
            "name" => "Panama",
            "icon" => "/pimcore/static6/img/flat-color-icons/stack_of_photos.svg",
            "id" => 3,
            "rootfolder" => "/examples/panama",
            "showroot" => true,
            "position" => "right",
            "sort" => "-15",                                                    // show in on the top
            "expanded" => false,
            "treeContextMenu" => [
                "asset" => [
                    "items" => [
                        "add" => [
//                            "hidden" => 1,                                    // hide "Add asset" menu (including subentries)
                            "items" => [
                                "upload" => 0,                                  // hide "Upload"
//                                "uploadCompatibility" => 0,                     // hide "Upload Compatibility Mode"
//                                "uploadZip" => 0,                               // hide "Upload ZIP Archive"
//                                "importFromServer" => 0,                        // don't show "Import from Server"
                                "uploadFromUrl" => 0                            // hide "Upload From URL"
                            ]
                        ],
                        "addFolder" => 1,                                       // show (!) "Add Folder" (shown by default anyway)
                        "rename" => 0,                                          // hide "Rename"
//                        "copy" => 0,                                            // hide "Copy"
//                        "cut" => 0,                                             // hide "Cut"
                        "paste" => 0,                                           // hide "Paste"
                        "pasteCut" => 0,                                        // hide "Paste cut element"
                        "delete" => 0,                                          // hide "Delete"
                        "searchAndMove" => 0,                                   // hide "Search And Move"
                        "lock" => 0,                                            // hide "Lock"
                        "unlock" => 0,                                          // hide "Unlock"
                        "lockAndPropagate" => 0,                                // hide "Lock and propagate"
                        "unlockAndPropagate" => 0,                              // hide "Unlock and propagate"
                        "reload" => [                                           // show reload (shown by default anyway)
                            "hidden" => false
                        ]
                    ]
                ]
            ]
        ]
    ]
];
