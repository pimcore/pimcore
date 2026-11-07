<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Normalizer\NormalizerInterface;

/**
 * Class MultiValue
 *
 * @package Pimcore\Model\DataObject\ClassDefinition\Data
 *
 * A classification store key that may hold more than one value, and that declares which qualifiers
 * it permits: the variants a value can be written under, and whether the key holds an ordered list
 * per slot. What a slot holds is the value of the inner type, which this one wraps the way
 * {@see EncryptedField} wraps its own delegate - the declarations live in this definition rather
 * than in a column of their own.
 *
 * Whether those declarations can actually be stored is a property of the engine behind the store,
 * {@see \Pimcore\Model\DataObject\Classificationstore\Engine\EngineFeature}.
 */
class MultiValue extends Data implements TypeDeclarationSupportInterface, NormalizerInterface
{
    /**
     * The declared variants, each an entry carrying a `name` and an optional `label`. Empty by
     * default, which is a key addressable under the default variant alone.
     *
     * @internal
     *
     * @var array<int, array<string, string>>
     */
    public array $variants = [];

    /**
     * @internal
     */
    public bool $multiValue = false;

    /**
     * How many values one slot may hold, or null for no declared limit. Read by
     * \Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore::checkValidity() rather
     * than enforced on write, so that an over-long list is reported as a field-level failure.
     *
     * @internal
     */
    public ?int $maximum = null;

    /**
     * The inner type of the key, or null for a definition blob that names none - a stored key config
     * written before the member existed, or an import that left it out, reaches every reader of this
     * property, so it answers "no inner type" rather than nothing at all.
     *
     * @internal
     */
    public ?string $delegateDatatype = null;

    /**
     * @internal
     */
    public Data|array|null $delegate = null;

    /**
     * @return array<int, array<string, string>>
     */
    public function getVariants(): array
    {
        return $this->variants;
    }

    /**
     * Takes the declarations as they come out of the key's definition blob as well as the ones it
     * already holds: {@see Data::setValues()} is run a second time by
     * {@see \Pimcore\Model\DataObject\ClassDefinition\Helper\VarExport::__set_state()}, on the
     * properties of the instance it rebuilds.
     *
     * @param array<int|string, array<string, mixed>|string> $variants
     *
     * @return $this
     */
    public function setVariants(array $variants): static
    {
        $declared = [];

        foreach ($variants as $variant) {
            $name = is_array($variant) ? ($variant['name'] ?? null) : $variant;
            if (!is_string($name) || $name === '') {
                continue;
            }

            $declaration = ['name' => $name];

            $label = is_array($variant) ? ($variant['label'] ?? null) : null;
            if (is_string($label) && $label !== '') {
                $declaration['label'] = $label;
            }

            $declared[] = $declaration;
        }

        $this->variants = $declared;

        return $this;
    }

    /**
     * The names of the declared variants, which is what a write is checked against.
     *
     * @return string[]
     */
    public function getVariantNames(): array
    {
        return array_column($this->variants, 'name');
    }

    public function isMultiValue(): bool
    {
        return $this->multiValue;
    }

    /**
     * `true` declares an unbounded list, an integer declares the list and its maximum in one, and
     * `false` clears both - the shape the key's definition blob carries either way.
     *
     * @return $this
     */
    public function setMultiValue(bool|int $multiValue): static
    {
        if (is_int($multiValue)) {
            $this->multiValue = $multiValue > 0;
            $this->maximum = $multiValue > 0 ? $multiValue : null;

            return $this;
        }

        $this->multiValue = $multiValue;

        if (!$multiValue) {
            $this->maximum = null;
        }

        return $this;
    }

    public function getMaximum(): ?int
    {
        return $this->maximum;
    }

    /**
     * @return $this
     */
    public function setMaximum(?int $maximum): static
    {
        $this->maximum = $maximum;

        return $this;
    }

    public function getDelegateDatatype(): ?string
    {
        return $this->delegateDatatype;
    }

    public function setDelegateDatatype(?string $delegateDatatype): void
    {
        $this->delegateDatatype = $delegateDatatype;
    }

    public function getDelegate(): Data|array|null
    {
        return $this->delegate;
    }

    public function setDelegate(Data|array|null $delegate): void
    {
        $this->delegate = $delegate;
    }

    /**
     * @see Data::getDataForEditmode
     */
    public function getDataForEditmode(mixed $data, ?DataObject\Concrete $object = null, array $params = []): mixed
    {
        $fd = $this->getDelegate();

        return $fd instanceof Data ? $fd->getDataForEditmode($data, $object, $params) : null;
    }

    /**
     * @see Data::getDataFromEditmode
     */
    public function getDataFromEditmode(mixed $data, ?DataObject\Concrete $object = null, array $params = []): mixed
    {
        $fd = $this->getDelegate();

        return $fd instanceof Data ? $fd->getDataFromEditmode($data, $object, $params) : null;
    }

    /**
     * The mandatory flag of the key itself is checked first: it is set on this definition - by the
     * key's assignment to a group among others - and the inner type carries one of its own, so
     * delegating alone would never consult it.
     */
    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        parent::checkValidity($data, $omitMandatoryCheck, $params);

        $fd = $this->getDelegate();
        if ($fd instanceof Data) {
            $fd->checkValidity($data, $omitMandatoryCheck, $params);
        }
    }

    public function isEmpty(mixed $data): bool
    {
        $fd = $this->getDelegate();

        return $fd instanceof Data ? $fd->isEmpty($data) : true;
    }

    public function getVersionPreview(mixed $data, ?DataObject\Concrete $object = null, array $params = []): string
    {
        $fd = $this->getDelegate();

        return $fd instanceof Data ? $fd->getVersionPreview($data, $object, $params) : '';
    }

    public function normalize(mixed $value, array $params = []): mixed
    {
        $fd = $this->getDelegate();

        return $fd instanceof NormalizerInterface ? $fd->normalize($value, $params) : null;
    }

    public function denormalize(mixed $value, array $params = []): mixed
    {
        $fd = $this->getDelegate();

        return $fd instanceof NormalizerInterface ? $fd->denormalize($value, $params) : null;
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return null;
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return null;
    }

    public function getPhpdocInputType(): ?string
    {
        return $this->delegate instanceof Data ? $this->delegate->getPhpdocInputType() : null;
    }

    public function getPhpdocReturnType(): ?string
    {
        return $this->delegate instanceof Data ? $this->delegate->getPhpdocReturnType() : null;
    }

    public function getFieldType(): string
    {
        return 'multiValue';
    }
}
