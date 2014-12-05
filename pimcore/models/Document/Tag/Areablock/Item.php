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
 * @package    Document
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Document\Tag\Areablock;

use Pimcore\Model;

class Item
{
    /**
     * @var Model\Document\Page
     */
    protected $doc;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var int
     */
    protected $index;

    /**
     * @param Model\Document\Page $doc
     * @param               $name
     * @param               $index
     */
    public function __construct(Model\Document\Page $doc, $name, $index)
    {
        $this->doc = $doc;
        $this->name = $name;
        $this->index = $index;
    }


    /**
     * @param $name
     *
     * @return Model\Document\Page
     */
    public function getElement($name)
    {
        $id = sprintf('%s%s%d', $name, $this->name, $this->index);
        $element = $this->doc->getElement( $id );
        $element->suffixes = array( $this->name );

        return $element;
    }

    /**
     * @param $func
     * @param $args
     *
     * @return Model\Document\Page*|null
     */
    public function __call($func, $args)
    {
        $element = $this->getElement($args[0]);
        $class = "\\Pimcore\\Model\\Document\\Tag\\" . str_replace('get', '', $func);

        if(!strcasecmp(get_class($element), $class))
        {
            return $element;
        }
    }
}
