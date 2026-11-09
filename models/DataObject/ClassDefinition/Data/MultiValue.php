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
use Pimcore\Model\Element\ValidationException;
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
 *
 * WHAT THIS WRAPPER FORWARDS. The declarations are its own; everything about the value is the inner
 * type's, and a caller that asks the wrapper has to be answered as the inner type would answer. So
 * it hands on, in each case returning the inner type's answer and, where there is no inner type,
 * the answer the question has WITHOUT one - which for a question about the value is the value
 * itself, never null. A key whose definition blob names no inner type is accepted by the loader
 * ({@see \Pimcore\Model\DataObject\Classificationstore\Service::getFieldDefinitionFromJson()}) and
 * by the capability check, which documents it as the key that "normalizes the plain value
 * unchanged" ({@see \Pimcore\Model\DataObject\Classificationstore\KeyTypeSupport::isHeldInValueColumns()});
 * answering null instead would store nothing for it and read nothing back, losing the value without
 * an error. What is forwarded:
 *
 *  - the value itself: `normalize()`, `denormalize()`, `getDataForEditmode()`,
 *    `getDataFromEditmode()`, `getVersionPreview()`, `isEmpty()`, `getPhpdocInputType()`,
 *    `getPhpdocReturnType()`
 *  - validation: `checkValidity()`, after the wrapper's own mandatory check - the key's assignment to
 *    a group sets that flag on THIS definition, so delegating alone would never consult it. That
 *    check asks the inner type whether the value is empty, so the wrapper refuses as mandatory
 *    exactly what the inner type would refuse
 *  - the pre-read hook: `preGetData()`, which is what filters the unpublished targets of a relation
 *    out of a read ({@see ManyToManyObjectRelation::preGetData()})
 *  - the element graph: `resolveDependencies()` and `getCacheTags()`, so a wrapped relation's
 *    targets reach the dependency rows and the cache tags of the object that holds them
 *  - `supportsInheritance()`, so a wrapped `consent` declines inheritance as an unwrapped one does
 *
 * What it deliberately does NOT forward:
 *
 *  - `getFieldType()`, which is `multiValue`: it is the type the key is DEFINED with, and it is what
 *    resolves this definition from a key config in the first place
 *  - `getParameterTypeDeclaration()` / `getReturnTypeDeclaration()`, which answer null: a slot can
 *    hold a list, so the inner type's single-value declaration is not the shape of what is held
 *  - `ResourcePersistenceAwareInterface`, `CustomResourcePersistingInterface` and the marshaller:
 *    where the value goes is the engine's question, and both engines resolve the inner definition
 *    at their own boundary ({@see \Pimcore\Model\DataObject\Classificationstore\Service::getValueDefinition()}).
 *    A wrapper implementing them would offer a second answer to it. So every OTHER caller that
 *    wants the stored form of a value has to resolve the inner definition through that same
 *    boundary rather than asking this one - the search index does
 *    ({@see \Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore::getDataForSearchIndex()}),
 *    and read off the wrapper it indexed nothing at all for a `multiValue` key
 *  - `AbstractRelations`: a wrapped relation is stored as its normalized value rather than as a
 *    {@see \Pimcore\Model\DataObject\Classificationstore\RelationDescriptor}, so the lazy-resolution
 *    path, the per-relation metadata table and the validity check that reads descriptors are not
 *    entered for one. Its value round-trips through the document, its columns included, and the
 *    engine refuses it where it cannot hold a relation at all
 *  - `LayoutDefinitionEnrichmentInterface`: the editor surface of a `multiValue` key is the Studio
 *    sibling's, and core renders no layout for the wrapper to enrich
 */
class MultiValue extends Data implements
    PreGetDataInterface,
    TypeDeclarationSupportInterface,
    NormalizerInterface
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
     * A BOOLEAN therefore carries no maximum and clears the one in force, whichever of the two it
     * is: `true` declares an unbounded list and `false` declares no list at all, and neither has a
     * limit to bound. `setMultiValue(3)` followed by `setMultiValue(true)` left the maximum at 3,
     * and a fourth value was still refused by {@see Classificationstore::checkValidity()} on a key
     * documented as unbounded.
     *
     * That is what an explicit call means, and it is all this setter answers for. The ORDER a blob
     * carries the two members in is not its problem: {@see self::setValues()} applies the
     * declaration before the maximum whatever order it reads them in, so hydration cannot have its
     * limit destroyed by a declaration that happens to follow it. The two were entangled before -
     * the setter kept a flag for which call had set the maximum, and {@see self::setMaximum()} reset
     * that flag - so a definition that had round-tripped was hydrated as "set on its own" and
     * `setMultiValue(true)` no longer cleared anything: the same call cleared the limit before
     * persistence and left it in place after a reload.
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
        $this->maximum = null;

        return $this;
    }

    /**
     * The list declaration is applied BEFORE the maximum, whatever order the members are read in.
     *
     * Hydration order is handled here rather than in {@see self::setMultiValue()}, which answers for
     * what an explicit call means and nothing else. A boolean declaration clears the maximum, so a
     * blob holding `maximum` before `multiValue` would have its limit destroyed by the declaration
     * that follows it - and `maximum` is a member of its own in the blob, carried for the bounded
     * list the key declares.
     *
     * Both paths that rebuild a key definition run this walk:
     * {@see \Pimcore\Model\DataObject\Classificationstore\Service::getFieldDefinitionFromJson()}
     * runs it over the decoded blob and then again over the instance's own properties, through
     * {@see \Pimcore\Model\DataObject\ClassDefinition\Helper\VarExport::__set_state()}.
     *
     * @param array<string, mixed> $data
     * @param string[] $blockedKeys
     *
     * @return $this
     */
    public function setValues(array $data = [], array $blockedKeys = []): static
    {
        if (array_key_exists('maximum', $data)) {
            $maximum = $data['maximum'];
            unset($data['maximum']);
            $data['maximum'] = $maximum;
        }

        return parent::setValues($data, $blockedKeys);
    }

    public function getMaximum(): ?int
    {
        return $this->maximum;
    }

    /**
     * The limit on its own, which is how the bounded list a key declares is hydrated -
     * {@see self::setValues()} applies it after the declaration, so the two cannot fight over the
     * order a blob happens to carry them in.
     *
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

        return $fd instanceof Data ? $fd->getDataForEditmode($data, $object, $params) : $data;
    }

    /**
     * @see Data::getDataFromEditmode
     */
    public function getDataFromEditmode(mixed $data, ?DataObject\Concrete $object = null, array $params = []): mixed
    {
        $fd = $this->getDelegate();

        return $fd instanceof Data ? $fd->getDataFromEditmode($data, $object, $params) : $data;
    }

    /**
     * The mandatory flag of the key itself is checked first: it is set on this definition - by the
     * key's assignment to a group among others - and the inner type carries one of its own, so
     * delegating alone would never consult it.
     *
     * It is answered with the INNER TYPE's idea of emptiness, {@see self::isEmpty()}, rather than
     * with the one {@see Data::checkValidity()} computes for itself. The base check reads a value
     * through `empty()`, under which `false` is an absent answer; a `checkbox` and a `booleanSelect`
     * both hold `false` as an answer of its own ({@see Checkbox::isEmpty()},
     * {@see BooleanSelect::isEmpty()}), so a mandatory `multiValue<checkbox>` or
     * `multiValue<booleanSelect>` whose answer was "no" was refused as empty before its own
     * validator ever ran - and refused by the wrapper, where the unwrapped type accepts it.
     *
     * Emptiness has to be the one question here, because it is also what decides which slot of a
     * qualified key answers the mandatory check at all
     * ({@see Classificationstore::collectKeyViolations()}): a wrapper answering it one way and
     * `isEmpty()` the other would skip a slot as empty and then refuse it as mandatory.
     */
    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        if (!$omitMandatoryCheck && $this->getMandatory() && $this->isEmpty($data)) {
            throw new ValidationException('Empty mandatory field [ ' . $this->getName() . ' ]');
        }

        $fd = $this->getDelegate();
        if ($fd instanceof Data) {
            $fd->checkValidity($data, $omitMandatoryCheck, $params);
        }
    }

    public function isEmpty(mixed $data): bool
    {
        $fd = $this->getDelegate();

        return $fd instanceof Data ? $fd->isEmpty($data) : parent::isEmpty($data);
    }

    /**
     * The pre-read hook of the inner type, which is what a read of a relation-typed value runs to
     * leave the unpublished targets out of it ({@see ManyToManyObjectRelation::preGetData()}) and
     * what a `wysiwyg` runs to rewrite its markup. A wrapper that did not implement the interface
     * would not be asked at all, and a wrapped `manyToManyObjectRelation` would hand out the
     * unpublished objects an unwrapped one hides.
     *
     * An inner type that carries no hook - and a key with no inner type at all - answers with the
     * value it was given, the way the container would have read it with no hook to run.
     */
    public function preGetData(mixed $container, array $params = []): mixed
    {
        $fd = $this->getDelegate();

        if (!$fd instanceof PreGetDataInterface) {
            return $params['data'] ?? null;
        }

        return $fd->preGetData($container, $params);
    }

    /**
     * The elements a value of the inner type refers to, as the dependency rows of the object holding
     * it. Without this the wrapper answers the base class's empty list, and a wrapped relation's
     * targets would carry no dependency at all - deleting one would not warn, and the rows of a key
     * nobody read would be wiped by an ordinary save.
     */
    public function resolveDependencies(mixed $data): array
    {
        $fd = $this->getDelegate();

        return $fd instanceof Data ? $fd->resolveDependencies($data) : [];
    }

    /**
     * The cache tags of a value of the inner type, for the same reason: a wrapped `image` tags the
     * asset it holds exactly as an unwrapped one does.
     */
    public function getCacheTags(mixed $data, array $tags = []): array
    {
        $fd = $this->getDelegate();

        return $fd instanceof Data ? $fd->getCacheTags($data, $tags) : $tags;
    }

    /**
     * Whether a value of this key may be read off an ancestor, which is the inner type's answer: the
     * qualifiers this definition declares say nothing about it, and a wrapped `consent` declines
     * inheritance for the reason an unwrapped one does.
     */
    public function supportsInheritance(): bool
    {
        $fd = $this->getDelegate();

        return $fd instanceof Data ? $fd->supportsInheritance() : parent::supportsInheritance();
    }

    public function getVersionPreview(mixed $data, ?DataObject\Concrete $object = null, array $params = []): string
    {
        $fd = $this->getDelegate();

        return $fd instanceof Data
            ? $fd->getVersionPreview($data, $object, $params)
            : parent::getVersionPreview($data, $object, $params);
    }

    /**
     * The stored form of the value, which is the inner type's.
     *
     * A key with no inner type - or one whose inner type normalizes nothing - stores the value as it
     * stands, the way a type that is not a {@see NormalizerInterface} is stored by both engines.
     * Answering null here instead wrote nothing for such a key and read nothing back, which lost the
     * value with no error anywhere: the loader builds a delegate-less wrapper rather than refusing
     * the definition blob, and the capability check accepts it on either engine on the strength of
     * its normalizing the plain value unchanged.
     */
    public function normalize(mixed $value, array $params = []): mixed
    {
        $fd = $this->getDelegate();

        return $fd instanceof NormalizerInterface ? $fd->normalize($value, $params) : $value;
    }

    /**
     * The value read back out of its stored form, the other half of {@see self::normalize()} and
     * delegate-less for the same reason.
     */
    public function denormalize(mixed $value, array $params = []): mixed
    {
        $fd = $this->getDelegate();

        return $fd instanceof NormalizerInterface ? $fd->denormalize($value, $params) : $value;
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
