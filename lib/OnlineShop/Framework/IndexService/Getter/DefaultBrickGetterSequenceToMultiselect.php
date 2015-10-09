<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */


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
            return OnlineShop_Framework_IndexService_Tenant_IWorker::MULTISELECT_DELIMITER .
                implode(OnlineShop_Framework_IndexService_Tenant_IWorker::MULTISELECT_DELIMITER, $values) .
            OnlineShop_Framework_IndexService_Tenant_IWorker::MULTISELECT_DELIMITER;
        } else {
            return null;
        }


    }
}
