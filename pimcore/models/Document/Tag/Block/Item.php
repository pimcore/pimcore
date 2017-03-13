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
 * @package    Document
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
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
    protected $suffixes = [];

    /**
     * @param Model\Document\PageSnippet $doc
     * @param int                        $index
     * @param array                      $suffixes
     */
    public function __construct(Model\Document\PageSnippet $doc, $index, array $suffixes)
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
        foreach ($this->suffixes as $item) {
            if (preg_match('#[^\d]{1}(?<index>[\d]+)$#i', $item, $match)) {
                $root .= $match['index'] . '_';
            }
        }
        $root .= $this->index;
        $id = $root;

        $element = $this->doc->getElement($id);
        if ($element) {
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
        $class = "Pimcore\\Model\\Document\\Tag\\" . str_replace('get', '', $func);

        if (!strcasecmp(get_class($element), $class)) {
            return $element;
        } elseif ($element === null) {
            return new $class;
        }
    }
}
