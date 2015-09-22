<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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
        return $string;
    }
}
