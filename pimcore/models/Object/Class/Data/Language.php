<?php 
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Object_Class
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Object_Class_Data_Language extends Object_Class_Data_Select {

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "language";

    /**
     * @var bool
     */
    public $onlySystemLanguages = false;


    public function configureOptions () {

        $validLanguages = (array) Pimcore_Tool::getValidLanguages();
        $locales = Pimcore_Tool::getSupportedLocales();
        $options = array();

        foreach ($locales as $short => $translation) {

            if($this->getOnlySystemLanguages()) {
                if(!in_array($short, $validLanguages)) {
                    continue;
                }
            }

            $options[] = array(
                "key" => $translation,
                "value" => $short
            );
        }

        $this->setOptions($options);
    }

    /**
     * @return bool
     */
    public function getOnlySystemLanguages () {
        return $this->onlySystemLanguages;
    }

    /**
     * @param bool $value
     */
    public function setOnlySystemLanguages ($value) {
        $this->onlySystemLanguages = (bool) $value;
    }



    /*public function __sleep () {
        //$this->configureOptions();

        return get_object_vars($this);
    }
    */

    public function __wakeup () {
        $this->configureOptions();
    }

   
}
