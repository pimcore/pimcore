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
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Document\Tag\Block;

use Pimcore\Model\Document;

class Item extends AbstractBlockItem
{
    protected function getItemType(): string
    {
        return 'block';
    }

    /**
     * @param $func
     * @param $args
     *
     * @return Document\Tag|null
     */
    public function __call($func, $args)
    {
        $element = $this->getElement($args[0]);
        $class = 'Pimcore\\Model\\Document\\Tag\\' . str_replace('get', '', $func);

        if (!strcasecmp(get_class($element), $class)) {
            return $element;
        } elseif ($element === null) {
            return new $class;
        }
    }
}
