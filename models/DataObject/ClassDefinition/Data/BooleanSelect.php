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

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Normalizer\NormalizerInterface;

class BooleanSelect extends Data implements
    ResourcePersistenceAwareInterface,
    QueryResourcePersistenceAwareInterface,
    TypeDeclarationSupportInterface,
    EqualComparisonInterface,
    VarExporterInterface,
    NormalizerInterface
{
    use Model\DataObject\Traits\SimpleComparisonTrait;
    use Extension\ColumnType;
    use Extension\QueryColumnType;
    use DataObject\Traits\SimpleNormalizerTrait;

    /** storage value for yes */
    const YES_VALUE = 1;

    /** storage value for no */
    const NO_VALUE = -1;

    /** storage value for empty */
    const EMPTY_VALUE = null;

    /** edit mode valze for empty */
    const EMPTY_VALUE_EDITMODE = 0;

    /**
     * Available options to select - Default options
     *
     * @var array
     */
    const DEFAULT_OPTIONS = [
        [
            'key' => 'empty',
            'value' => self::EMPTY_VALUE_EDITMODE,
        ],
        [
            'key' => 'yes',
            'value' => self::YES_VALUE,
        ],
        [
            'key' => 'no',
            'value' => self::NO_VALUE,
        ],
    ];

    /**
     * Static type of this element
     *
     * @internal
     *
     * @var string
     */
    public string $fieldtype = 'booleanSelect';

    /**
     * @internal
     *
     * @var string
     */
    public string $yesLabel;

    /**
     * @internal
     *
     * @var string
     */
    public string $noLabel;

    /**
     * @internal
     *
     * @var string
     */
    public string $emptyLabel;

    /**
     * @internal
     *
     * @var array|array[]
     */
    public array $options = self::DEFAULT_OPTIONS;

    /**
     * @internal
     *
     * @var string|int
     */
    public string|int $width = 0;

    /**
     * Type for the column to query
     *
     * @internal
     *
     * @var string
     */
    public $queryColumnType = 'tinyint(1) null';

    /**
     * Type for the column
     *
     * @internal
     *
     * @var string
     */
    public $columnType = 'tinyint(1) null';

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): static
    {
        // nothing to do
        return $this;
    }

    public function getWidth(): int|string
    {
        return $this->width;
    }

    public function setWidth(int|string $width): static
    {
        if (is_numeric($width)) {
            $width = (int)$width;
        }
        $this->width = $width;

        return $this;
    }

    /**
     * @param mixed $data
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return bool|null
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     */
    public function getDataFromResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?bool
    {
        if (is_numeric($data)) {
            $data = (int) $data;
        }

        if ($data === self::YES_VALUE) {
            return true;
        } elseif ($data === self::NO_VALUE) {
            return false;
        }

        return null;
    }

    /**
     * @param mixed $data
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return int|null
     *@see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     */
    public function getDataForQueryResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?int
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @param mixed $data
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return int|null
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     */
    public function getDataForResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?int
    {
        if (is_numeric($data)) {
            $data = (bool) $data;
        }
        if ($data === true) {
            return self::YES_VALUE;
        } elseif ($data === false) {
            return self::NO_VALUE;
        }

        return null;
    }

    /**
     * @param mixed $data
     * @param DataObject\Concrete|null $object
     * @param array $params
     *
     * @return string
     * @see Data::getVersionPreview
     *
     */
    public function getVersionPreview(mixed $data, DataObject\Concrete $object = null, array $params = []): string
    {
        if ($data === true) {
            return $this->getYesLabel();
        }
        if ($data === false) {
            return $this->getNoLabel();
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function isDiffChangeAllowed(Concrete $object, array $params = []): bool
    {
        return true;
    }

    /** See parent class.
     * @param mixed $data
     * @param DataObject\Concrete|null $object
     * @param array $params
     *
     * @return array|null
     */
    public function getDiffDataForEditMode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?array
    {
        $result = [];

        $diffdata = [];
        $diffdata['data'] = $data;
        $diffdata['disabled'] = false;
        $diffdata['field'] = $this->getName();
        $diffdata['key'] = $this->getName();
        $diffdata['type'] = $this->fieldtype;

        $value = '';
        foreach ($this->options as $option) {
            if ($option['value'] == $data) {
                $value = $option['key'];

                break;
            }
        }

        $diffdata['value'] = $value;
        $diffdata['title'] = !empty($this->title) ? $this->title : $this->name;

        $result[] = $diffdata;

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = [])
    {
        //TODO mandatory probably doesn't make much sense
        if (!$omitMandatoryCheck && $this->getMandatory() && $this->isEmpty($data)) {
            throw new Model\Element\ValidationException('Empty mandatory field [ ' . $this->getName() . ' ]');
        }
    }

    public function isEmpty(mixed $data): bool
    {
        return $data !== true && $data !== false;
    }

    /**
     * @param DataObject\ClassDefinition\Data\BooleanSelect $masterDefinition
     */
    public function synchronizeWithMasterDefinition(DataObject\ClassDefinition\Data $masterDefinition)
    {
        $this->options = $masterDefinition->options;
        $this->width = $masterDefinition->width;
    }

    public function getYesLabel(): string
    {
        return $this->yesLabel;
    }

    public function setYesLabel(?string $yesLabel): static
    {
        $this->yesLabel = $yesLabel;
        $this->setOptionsEntry(self::YES_VALUE, $yesLabel);

        return $this;
    }

    public function setOptionsEntry($value, $label)
    {
        if (!is_array($this->options)) {
            $this->options = [
                ['key' => $label,
                'value' => $value,
                ],

            ];
        } else {
            foreach ($this->options as $idx => $option) {
                if ($option['value'] == $value) {
                    $option['key'] = $label;
                    $this->options[$idx] = $option;

                    break;
                }
            }
        }
    }

    public function getNoLabel(): string
    {
        return $this->noLabel;
    }

    public function setNoLabel($noLabel): static
    {
        $this->noLabel = $noLabel;
        $this->setOptionsEntry(self::NO_VALUE, $noLabel);

        return $this;
    }

    public function getEmptyLabel(): string
    {
        return $this->emptyLabel;
    }

    public function setEmptyLabel($emptyLabel): static
    {
        $this->emptyLabel = $emptyLabel;
        $this->setOptionsEntry(self::EMPTY_VALUE_EDITMODE, $emptyLabel);

        return $this;
    }

    /**
     * @param bool|null $data
     * @param null|Model\DataObject\Concrete $object
     * @param array $params
     *
     * @return int
     */
    public function getDataForGrid(?bool $data, Concrete $object = null, array $params = []): int
    {
        return $this->getDataForEditmode($data, $object, $params);
    }

    /**
     * @param mixed $data
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return int
     * @see Data::getDataForEditmode
     *
     */
    public function getDataForEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): int
    {
        if ($data === true) {
            return self::YES_VALUE;
        }
        if ($data === false) {
            return self::NO_VALUE;
        }

        return self::EMPTY_VALUE_EDITMODE;
    }

    /**
     * @param string $data
     * @param DataObject\Concrete|null $object
     * @param array $params
     *
     * @return bool|null
     */
    public function getDataFromGridEditor(string $data, Concrete $object = null, array $params = []): ?bool
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     * @param mixed $data
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return bool|null
     * @see Data::getDataFromEditmode
     *
     */
    public function getDataFromEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?bool
    {
        if ((int)$data === 1) {
            return true;
        } elseif ((int)$data === -1) {
            return false;
        }

        return null;
    }

    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        return $oldValue === $newValue;
    }

    /**
     * {@inheritdoc}
     */
    public function getForCsvExport(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        $value = $this->getDataFromObjectParam($object, $params);
        if ($value === null) {
            $value = '';
        } elseif ($value) {
            $value = '1';
        } else {
            $value = '0';
        }

        return $value;
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return '?bool';
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?bool';
    }

    public function getPhpdocInputType(): ?string
    {
        return 'bool|null';
    }

    public function getPhpdocReturnType(): ?string
    {
        return 'bool|null';
    }
}
