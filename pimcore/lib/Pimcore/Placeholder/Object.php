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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Placeholder;

use Pimcore\Model;

class Object extends AbstractPlaceholder
{

    /**
     * Returns a value for test replacement
     *
     * @return string
     */
    public function getTestValue()
    {
        return '<span class="testValue">Name of the Object</span>';
    }

    /**
     * Gets a object by it's id and replaces the placeholder width the value form the called "method"
     *
     * example: %Object(object_id,{"method" : "getId"});
     * @return string
     */
    public function getReplacement()
    {
        $string = '';
        $object = is_object($this->getValue()) ? $this->getValue() : Model\Object\Concrete::getById($this->getValue());

        if ($object) {
            if (is_string($this->getPlaceholderConfig()->method) && method_exists($object, $this->getPlaceholderConfig()->method)) {
                $string = $object->{$this->getPlaceholderConfig()->method}($this->getLocale());
            }
        }
        if (is_bool($this->getPlaceholderConfig()->nl2br)) {
            $string = nl2br($string);
        }

        return $string;
    }
}
