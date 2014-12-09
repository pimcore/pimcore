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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Webservice\Data\Document;

use Pimcore\Model;
use Pimcore\Model\Webservice;

class PageSnippet extends Model\Webservice\Data\Document {
    
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
     * @var Webservice\Data\Document\Element[]
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

            $el = new Webservice\Data\Document\Element();
            $el->name = $element->getName();
            $el->type = $element->getType();
            $el->value = $element->getForWebserviceExport();
            $this->elements[] = $el;

        }
    }

    /**
     * @param $object
     * @param bool $disableMappingExceptions
     * @param null $idMapper
     * @throws \Exception
     */
    public function reverseMap ($object, $disableMappingExceptions = false, $idMapper = null) {
        parent::reverseMap($object, $disableMappingExceptions, $idMapper);
        
        $object->childs = null;
        $object->elements = array();

        if(is_array($this->elements)) {
            foreach ($this->elements as $element) {

                $tag = Model\Document\Tag::factory($element->type,$element->name,$this->id);
                $tag->getFromWebserviceImport($element, $idMapper);

                $object->elements[$element->name] = $tag;
            }
        }
    }
}
