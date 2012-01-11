<?php

class OnlineShop_Framework_IndexService_Getter_DefaultBrickGetterSequence implements OnlineShop_Framework_IndexService_Getter {

    public static function get($object, $config = null) {
        $sourceList = $config->source;

        if($sourceList->brickfield) {
            $sourceList = array($sourceList);
        }

        foreach($sourceList as $source) {
            $brickContainerGetter = "get" . ucfirst($source->brickfield);

            if(method_exists($object, $brickContainerGetter)) {
                $brickContainer = $object->$brickContainerGetter();

                $brickGetter = "get" . ucfirst($source->bricktype);
                $brick = $brickContainer->$brickGetter();
                if($brick) {
                    $fieldGetter = "get" . ucfirst($source->fieldname);
                    $value = $brick->$fieldGetter();
                    if($value) {

                        return $value;
                    }
                }
            }

        }
    }
}
