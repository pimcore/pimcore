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

namespace Pimcore\Model\Document\Tag\Block;

use Pimcore\Model;

class Item
{
    /**
     * @var Model\Document\Page
     */
    protected $doc;

    /**
     * @var int
     */
    protected $index;

    /**
     * @var string[]
     */
    protected $suffixes = array();

    /**
     * @param Model\Document\Page $doc
     * @param int           $index
     * @param array         $suffixes
     */
    public function __construct(Model\Document\Page $doc, $index, array $suffixes)
    {
        $this->doc = $doc;
        $this->index = $index;
        $this->suffixes = $suffixes;
    }


    /**
     * @param $name
     *
     * @return Model\Document\Tag
     */
    public function getElement($name)
    {
        $root = $name . implode('_', $this->suffixes);
        foreach($this->suffixes as $item)
        {
            if(preg_match('#[^\d]{1}(?<index>[\d]+)$#i', $item, $match))
            {
                $root .= $match['index'] . '_';
            }
        }
        $root .= $this->index;
        $id = $root;

        $element = $this->doc->getElement( $id );
        if($element)
        {
            $element->suffixes = $this->suffixes;
        }

        return $element;
    }


    /**
     * @param $func
     * @param $args
     *
     * @return Model\Document\Tag|null
     */
    public function __call($func, $args)
    {
        $element = $this->getElement($args[0]);
        $class = "\\Pimcore\\Model\\Document\\Tag\\" . str_replace('get', '', $func);

        if(!strcasecmp(get_class($element), $class))
        {
            return $element;
        }
        else if($element === NULL)
        {
            return new $class;
        }
    }
}
