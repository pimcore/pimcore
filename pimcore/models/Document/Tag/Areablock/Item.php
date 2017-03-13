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
     * @param Model\Document\PageSnippet $doc
     * @param string                     $name
     * @param int                        $index
     */
    public function __construct(Model\Document\PageSnippet $doc, $name, $index)
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
        $element = $this->doc->getElement($id);
        $element->suffixes = [ $this->name ];

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
        $class = "Pimcore\\Model\\Document\\Tag\\" . str_replace('get', '', $func);

        if (!strcasecmp(get_class($element), $class)) {
            return $element;
        }
    }
}
