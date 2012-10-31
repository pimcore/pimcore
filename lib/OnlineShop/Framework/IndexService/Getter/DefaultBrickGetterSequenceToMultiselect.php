<?php

class OnlineShop_Framework_IndexService_Getter_DefaultBrickGetterSequenceToMultiselect implements OnlineShop_Framework_IndexService_Getter {

    public static function get($object, $config = null) {
        $sourceList = $config->source;

        $values = array();

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

                    if($source->invert == "true") {
                        $value = !$value;
                    }

                    if($value) {
                        if(is_bool($value) || $source->forceBool == "true") {
                            $values[] = $source->fieldname;
                        } else {
                            $values[] = $value;
                        }
                    }
                }
            } else {
                $fieldGetter = "get" . ucfirst($source->fieldname);
                if(method_exists($object, $fieldGetter)) {
                    $value = $object->$fieldGetter();

                    if($source->invert == "true") {
                        $value = !$value;
                    }

                    if($value) {
                        if(is_bool($value) || $source->forceBool == "true") {
                            $values[] = $source->fieldname;
                        } else {
                            $values[] = $value;
                        }
                    }
                }
            }

        }
        if(!empty($values)) {
            return OnlineShop_Framework_IndexService::MULTISELECT_DELIMITER .
                implode(OnlineShop_Framework_IndexService::MULTISELECT_DELIMITER, $values) .
                OnlineShop_Framework_IndexService::MULTISELECT_DELIMITER;
        } else {
            return null;
        }


    }
}
