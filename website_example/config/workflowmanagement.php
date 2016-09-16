<?php

return [
    "workflows" => [
        [
            "name" => "Products workflow",                                        
            "id" => 1,                                                          
            "workflowSubject" => [                                              
                "types" => ["object"],
                "classes" => [4],                       //this is the id of the produt class
                "assetTypes" => ["image", "video"]
            ],
            "enabled" => true,                                                  
            "defaultState" => "open",                                          
            "defaultStatus" => "todo",                                         
            "allowUnpublished"=> true,
        ]
    ]
];