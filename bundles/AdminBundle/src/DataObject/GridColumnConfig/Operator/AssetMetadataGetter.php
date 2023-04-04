<?php
declare(strict_types=1);

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\AdminBundle\DataObject\GridColumnConfig\Operator;

use Pimcore\Bundle\AdminBundle\DataObject\GridColumnConfig\ResultContainer;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Data\Hotspotimage;
use Pimcore\Model\Element\ElementInterface;

/**
 * @internal
 */
final class AssetMetadataGetter extends AbstractOperator
{
    private string $metaField;

    private ?string $locale = null;

    public function __construct(\stdClass $config, array $context = [])
    {
        parent::__construct($config, $context);

        $this->metaField = $config->metaField ?? '';
        $this->locale = $config->locale ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabeledValue(array|ElementInterface $element): ResultContainer|\stdClass|null
    {
        $result = new \stdClass();
        $result->label = $this->label;
        $result->value = null;

        $children = $this->getChildren();

        if ($children) {
            $newChildrenResult = [];

            foreach ($children as $c) {
                $childResult = $c->getLabeledValue($element);
                $childValues = $childResult->value ?? null;
                if ($childValues && !is_array($childValues)) {
                    $childValues = [$childValues];
                }

                $newValue = null;

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
                }

                $newChildrenResult[] = $newValue;
            }

            if (count($children) > 1) {
                $result->value = $newChildrenResult;
            } else {
                $result->value = $newChildrenResult[0];
            }
        }

        return $result;
    }

    public function getMetadata(Hotspotimage|Asset $value): mixed
    {
        $asset = $value;
        if ($value instanceof Hotspotimage) {
            $asset = $value->getImage();
        }

        if ($asset instanceof Asset) {
            $metaValue = $asset->getMetadata($this->getMetaField(), $this->getLocale());

            return $metaValue;
        }

        return null;
    }

    public function getMetaField(): mixed
    {
        return $this->metaField;
    }

    public function setMetaField(mixed $metaField): void
    {
        $this->metaField = $metaField;
    }

    public function setLocale(mixed $locale): void
    {
        $this->locale = $locale;
    }

    public function getLocale(): mixed
    {
        return $this->locale;
    }
}
