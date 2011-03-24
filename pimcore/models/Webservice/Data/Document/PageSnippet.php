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
 * @package    Webservice
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Webservice_Data_Document_PageSnippet extends Webservice_Data_Document {
    
    /**
     * @var string
     */
    public $controller;

    /**
     * @var string
     */
    public $action;

    /**
     * @var string
     */
    public $template;

    /**
     * @var Webservice_Data_Document_Element[]
     */
    public $elements;

    
    public function map ($object) {

        $originalElements = array();
        if(is_array($object->getElements())){
            $originalElements=$object->getElements();
        }

        parent::map($object);

        $this->elements = array();
        foreach($originalElements as $element) {

            $el = new Webservice_Data_Document_Element();
            $el->name = $element->getName();
            $el->type = $element->getType();
            $el->value = $element->getForWebserviceExport();
            $this->elements[] = $el;

        }
    }
    
    
    public function reverseMap ($object) {
        parent::reverseMap($object);
        
        $object->childs = null;
        $object->elements = array();

        if(is_array($this->elements)) {
            foreach ($this->elements as $element) {

                $tag = Document_Tag::factory($element->type,$element->name,$this->id);
                $tag->getFromWebserviceImport($element);

                $object->elements[$element->name] = $tag;
            }
        }
    }
}
