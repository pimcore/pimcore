<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Webservice
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Webservice\Data\Document;

use Pimcore\Model;
use Pimcore\Model\Webservice;

/**
 * @deprecated
 */
class PageSnippet extends Model\Webservice\Data\Document
{
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

    /**
     * @param Model\Document\PageSnippet $object
     * @param array|null $options
     */
    public function map($object, $options = null)
    {
        $originalEditables = [];
        if (is_array($object->getEditables())) {
            $originalEditables = $object->getEditables();
        }

        parent::map($object);

        $this->elements = [];
        foreach ($originalEditables as $element) {
            $el = new Webservice\Data\Document\Element();
            $el->name = $element->getName();
            $el->type = $element->getType();
            $el->value = $element->getForWebserviceExport();
            $this->elements[] = $el;
        }
    }

    /**
     * @param Model\Document\PageSnippet $object
     * @param bool $disableMappingExceptions
     * @param Model\Webservice\IdMapperInterface|null $idMapper
     *
     * @throws \Exception
     */
    public function reverseMap($object, $disableMappingExceptions = false, $idMapper = null)
    {
        parent::reverseMap($object, $disableMappingExceptions, $idMapper);

        $object->setChildren(null);
        $object->setEditables([]);

        if (is_array($this->elements)) {
            foreach ($this->elements as $element) {
                $tag = Model\Document\Tag::factory($element->type, $element->name, $this->id);
                $tag->getFromWebserviceImport($element, $object, [], $idMapper);

                $object->setEditable($element->name, $tag);
            }
        }
    }
}
