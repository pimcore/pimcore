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
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\DataObject\GridColumnConfig\Operator;

use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Data\Hotspotimage;

class AssetMetadataGetter extends AbstractOperator
{
    private $metaField;

    private $locale;

    public function __construct(\stdClass $config, $context = null)
    {
        parent::__construct($config, $context);

        $this->metaField = $config->metaField;
        $this->locale = $config->locale;
    }

    public function getLabeledValue($element)
    {
        $result = new \stdClass();
        $result->label = $this->label;
        $result->value = null;

        $childs = $this->getChilds();

        if ($childs) {
            $newChildsResult = [];

            foreach ($childs as $c) {
                $childResult = $c->getLabeledValue($element);
                $childValues = $childResult->value;
                if ($childValues && !is_array($childValues)) {
                    $childValues = [$childValues];
                }

                if (is_array($childValues)) {
                    foreach ($childValues as $value) {
                        if (is_array($value)) {
                            $newSubValues = [];
                            foreach ($value as $subValue) {
                                $subValue = $this->getMetadata($subValue);
                                $newSubValues[] = $subValue;
                            }
                            $newValue = $newSubValues;
                        } else {
                            $newValue = $this->getMetadata($value);
                        }
                    }
                } else {
                    $newValue = null;
                }

                $newChildsResult[] = $newValue;
            }

            if (count($childs) > 1) {
                $result->value = $newChildsResult;
            } else {
                $result->value = $newChildsResult[0];
            }
        }

        return $result;
    }

    /**
     * @param $value
     *
     * @return mixed
     */
    public function getMetadata($value)
    {
        $asset = $value;
        if ($value instanceof Hotspotimage) {
            $asset = $value->getImage();
        }

        if ($asset instanceof Asset) {
            $metaValue = $asset->getMetadata($this->getMetaField(), $this->getLocale());

            return $metaValue;
        }
    }

    /**
     * @return mixed
     */
    public function getMetaField()
    {
        return $this->metaField;
    }

    /**
     * @param mixed $metaField
     */
    public function setMetaField($metaField)
    {
        $this->metaField = $metaField;
    }

    /**
     * @param mixed $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * @return mixed
     */
    public function getLocale()
    {
        return $this->locale;
    }
}
