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
 * @package    Object|Class
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model;

class Countrymultiselect extends Model\DataObject\ClassDefinition\Data\Multiselect
{
    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'countrymultiselect';

    /** Restrict selection to comma-separated list of countries.
     * @var null
     */
    public $restrictTo = null;

    public function __construct()
    {
        $countries = \Pimcore::getContainer()->get('pimcore.locale')->getDisplayRegions();
        asort($countries);
        $options = [];

        foreach ($countries as $short => $translation) {
            if (strlen($short) == 2) {
                $options[] = [
                    'key' => $translation,
                    'value' => $short
                ];
            }
        }

        $this->setOptions($options);
    }

    /**
     * @param string $restrictTo
     */
    public function setRestrictTo($restrictTo)
    {
        /**
         * @extjs6
         */
        if (is_array($restrictTo)) {
            $restrictTo = implode(',', $restrictTo);
        }

        $this->restrictTo = $restrictTo;
    }

    /**
     * @return string
     */
    public function getRestrictTo()
    {
        return $this->restrictTo;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }
}
