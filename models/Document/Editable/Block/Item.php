<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Document\Editable\Block;

use Pimcore\Model\Document;

class Item extends AbstractBlockItem
{
    protected function getItemType(): string
    {
        return 'block';
    }

    /**
     * @param string $func
     * @param array $args
     *
     * @return Document\Editable|null
     */
    public function __call($func, $args)
    {
        $element = $this->getEditable($args[0]);
        $class = 'Pimcore\\Model\\Document\\Editable\\' . str_replace('get', '', $func);

        if ($element === null) {
            return new $class;
        }

        if (!strcasecmp(get_class($element), $class)) {
            return $element;
        }

        return null;
    }
}
