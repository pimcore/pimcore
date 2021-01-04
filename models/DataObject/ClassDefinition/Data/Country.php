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
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Model;
use Pimcore\Model\DataObject\ClassDefinition\Service;

class Country extends Model\DataObject\ClassDefinition\Data\Select
{
    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'country';

    /**
     * @var string|int
     */
    public $width = 0;

    /** Restrict selection to comma-separated list of countries.
     * @var string|null
     */
    public $restrictTo = null;

    public function __construct()
    {
        $this->buildOptions();
    }

    private function buildOptions()
    {
        $countries = \Pimcore::getContainer()->get(LocaleServiceInterface::class)->getDisplayRegions();
        asort($countries);
        $options = [];

        foreach ($countries as $short => $translation) {
            if (strlen($short) == 2) {
                $options[] = [
                    'key' => $translation,
                    'value' => $short,
                ];
            }
        }

        $this->setOptions($options);
    }

    /** True if change is allowed in edit mode.
     * @param Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return bool
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return true;
    }

    /**
     * @return string|int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param string|int $width
     *
     * @return $this
     */
    public function setWidth($width)
    {
        if (is_numeric($width)) {
            $width = (int)$width;
        }
        $this->width = $width;

        return $this;
    }

    /**
     * @param array|string|null $restrictTo
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
     * @return string|null
     */
    public function getRestrictTo()
    {
        return $this->restrictTo;
    }

    public function isFilterable(): bool
    {
        return true;
    }

    /**
     * @return $this
     */
    public function jsonSerialize()
    {
        if (Service::doRemoveDynamicOptions()) {
            $this->options = null;
        }

        return $this;
    }
}
