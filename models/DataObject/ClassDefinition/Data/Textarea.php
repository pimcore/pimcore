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
use Pimcore\Normalizer\NormalizerInterface;

class Textarea extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface
{
    use DataObject\Traits\DataHeightTrait;
    use DataObject\Traits\DataWidthTrait;
    use Model\DataObject\ClassDefinition\Data\Extension\Text;
    use Model\DataObject\Traits\SimpleComparisonTrait;
    use Model\DataObject\Traits\SimpleNormalizerTrait;

    /**
     * @internal
     *
     */
    public ?int $maxLength = null;

    /**
     * @internal
     */
    public bool $showCharCount = false;

    /**
     * @internal
     */
    public bool $excludeFromSearchIndex = false;

    public function getMaxLength(): ?int
    {
        return $this->maxLength;
    }

    public function setMaxLength(?int $maxLength): void
    {
        $this->maxLength = $maxLength;
    }

    public function isShowCharCount(): bool
    {
        return $this->showCharCount;
    }

    public function setShowCharCount(bool $showCharCount): void
    {
        $this->showCharCount = $showCharCount;
    }

    public function isExcludeFromSearchIndex(): bool
    {
        return $this->excludeFromSearchIndex;
    }

    /**
     * @return $this
     */
    public function setExcludeFromSearchIndex(bool $excludeFromSearchIndex): static
    {
        $this->excludeFromSearchIndex = $excludeFromSearchIndex;

        return $this;
    }

    /**
     * @param null|Model\DataObject\Concrete $object
     *
     * @see ResourcePersistenceAwareInterface::getDataForResource
     */
    public function getDataForResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?string
    {
        return $data;
    }

    /**
     * @param null|Model\DataObject\Concrete $object
     *
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     */
    public function getDataFromResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?string
    {
        return $data;
    }

    /**
     *
     *
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     */
    public function getDataForQueryResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?string
    {
        return $data;
    }

    /**
     * @param null|Model\DataObject\Concrete $object
     *
     * @see Data::getDataForEditmode
     *
     */
    public function getDataForEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?string
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Data::getDataFromEditmode
     *
     */
    public function getDataFromEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?string
    {
        if ($data === '') {
            return null;
        }

        return $data;
    }

    /**
     * Generates a pretty version preview (similar to getVersionPreview) can be either html or
     * a image URL. See the https://github.com/pimcore/object-merger bundle documentation for details
     *
     *
     */
    public function getDiffVersionPreview(?string $data, Model\DataObject\Concrete $object = null, array $params = []): array|string
    {
        if ($data) {
            $value = [];
            $data = str_replace("\r\n", '<br>', $data);
            $data = str_replace("\n", '<br>', $data);
            $data = str_replace("\r", '<br>', $data);

            $value['html'] = $data;
            $value['type'] = 'html';

            return $value;
        } else {
            return '';
        }
    }

    public function getDataForSearchIndex(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        if ($this->isExcludeFromSearchIndex()) {
            return '';
        } else {
            return parent::getDataForSearchIndex($object, $params);
        }
    }

    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        if (!$omitMandatoryCheck && $this->getMaxLength() !== null) {
            if ($data !== null && mb_strlen($data) > $this->getMaxLength()) {
                throw new Model\Element\ValidationException('Value in field [ ' . $this->getName() . " ] longer than max length of '" . $this->getMaxLength() . "'");
            }
        }

        parent::checkValidity($data, $omitMandatoryCheck);
    }

    public function isFilterable(): bool
    {
        return true;
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return '?string';
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?string';
    }

    public function getPhpdocInputType(): ?string
    {
        return 'string|null';
    }

    public function getPhpdocReturnType(): ?string
    {
        return 'string|null';
    }

    public function getColumnType(): string
    {
        return 'longtext';
    }

    public function getQueryColumnType(): string
    {
        return $this->getColumnType();
    }

    public function getFieldType(): string
    {
        return 'textarea';
    }
}
