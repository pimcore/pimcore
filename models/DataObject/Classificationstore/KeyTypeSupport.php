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

namespace Pimcore\Model\DataObject\Classificationstore;

use Pimcore\Element\MarshallerService;
use Pimcore\Loader\ImplementationLoader\Exception\UnsupportedException;
use Pimcore\Model\DataObject\ClassDefinition\Loader\DataLoaderInterface;
use Pimcore\Normalizer\NormalizerInterface;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

/**
 * Which field types a classification store key may be defined with, and what a storage engine can
 * hold of them.
 *
 * Public API deliberately, against the `@internal`-by-default rule: the key picker of the Studio
 * backend bundle has to disable a type the store cannot hold, and guessing which ones those are is
 * how the picker and the enforcement in {@see KeyConfig::save()} come apart. Neither answer reads
 * the database.
 *
 * The accepted set is not a list. It is every type the object data implementation loader resolves,
 * minus the exclusion names below, so a field type added to core - or by a bundle - is accepted
 * without this class being edited.
 */
final class KeyTypeSupport
{
    private const MARSHALLER_FORMAT = 'classificationstore';

    /**
     * The one type whose value is decided by another type: its marshaller encrypts what the
     * delegate's marshaller packed, so what it can hold is what the delegate can hold.
     */
    private const ENCRYPTED_FIELD_TYPE = 'encryptedField';

    /**
     * The field types a classification store key is never defined with, and the reason each one is
     * out. They are names rather than a derived property on purpose: every property that would
     * make this list look self-deriving takes types with it that belong in the set - the rule
     * "implements CustomResourcePersistingInterface" would take the relation types, which do.
     *
     * The list is scoped to the set this release unlocks: it says which of the types the document
     * engine makes storable are not accepted, and it does not reach back and remove support core
     * already ships. That is why `calculatedValue` appears here and is still neither refused at
     * definition time nor at write time - the classification store model computes it on read and
     * the classification store field definition builds it into the editmode payload, and both are
     * untouched.
     *
     * @var string[]
     */
    private const EXCLUDED_TYPES = [
        // the five container types: their value is the values of other field definitions, and a
        // key is one leaf of such a tree rather than a place to hang one
        'block',
        'objectbricks',
        'fieldcollections',
        'localizedfields',
        'classificationstore',
        // computed from the owning side of a relation rather than stored, so a key has nothing to write
        'reverseObjectRelation',
        // its value is a row of the route table `object_url_slugs`, keyed by (object, fieldname),
        // which a key cannot occupy - storing the slug and site id alone registers no route
        'urlSlug',
        // not one of the types unlocked here; the support core already ships for it is untouched
        'calculatedValue',
    ];

    /**
     * Return types of `normalize()` whose value the two value columns of an EAV row take as it is.
     * An undeclared return type is `mixed` and counts the same way.
     *
     * @var string[]
     */
    private const SCALAR_RETURN_TYPES = ['mixed', 'bool', 'false', 'true', 'int', 'float', 'string', 'null'];

    /**
     * @var array<string, bool>
     */
    private array $accepted = [];

    /**
     * @var array<string, bool>
     */
    private array $scalarValued = [];

    public function __construct(
        private readonly DataLoaderInterface $dataLoader,
        private readonly MarshallerService $marshallerService
    ) {
    }

    /**
     * Whether a key may be defined with this field type at all, on any engine.
     */
    public function isAcceptedType(string $type): bool
    {
        if (!isset($this->accepted[$type])) {
            $this->accepted[$type] = !in_array($type, self::EXCLUDED_TYPES, true)
                && $this->dataLoader->supports($type);
        }

        return $this->accepted[$type];
    }

    /**
     * Whether the value of this field type fits the two value columns of an EAV row - which is what
     * an engine that does not offer {@see Engine\EngineFeature::StructuredValues} has to hold it in.
     *
     * Two halves, and they need each other: a type whose `normalize()` declares a scalar-compatible
     * return type stores as it is, and a type with a `classificationstore_<fieldtype>` marshaller is
     * packed into the two columns by it. `table` and `multiselect` declare `mixed` and return an
     * array, and are held by their marshaller; `geopoint` declares `?array` and has none.
     *
     * $delegateType is the `delegateDatatype` of an `encryptedField` key and is ignored for every
     * other type. `null` on an `encryptedField` means no delegate was configured, which normalizes
     * the plain value unchanged and is therefore held exactly when a scalar is.
     *
     * The declared return type is a declaration, not a measurement: a type could declare `mixed`
     * and return a structure. {@see Engine\EavEngine} re-checks the value it is about to write, so
     * that such a type fails loudly rather than reaching a column that cannot hold it.
     */
    public function isHeldInValueColumns(string $type, ?string $delegateType = null): bool
    {
        if ($type === self::ENCRYPTED_FIELD_TYPE) {
            return $delegateType === null || $this->isHeldInValueColumns($delegateType);
        }

        if ($this->marshallerService->supportsFielddefinition(self::MARSHALLER_FORMAT, $type)) {
            return true;
        }

        return $this->normalizesToScalar($type);
    }

    /**
     * Whether the field definition of this type declares that it normalizes to something the two
     * value columns take as it is.
     */
    private function normalizesToScalar(string $type): bool
    {
        if (!isset($this->scalarValued[$type])) {
            $this->scalarValued[$type] = $this->readNormalizedReturnType($type);
        }

        return $this->scalarValued[$type];
    }

    private function readNormalizedReturnType(string $type): bool
    {
        try {
            $fieldDefinition = $this->dataLoader->build($type);
        } catch (UnsupportedException) {
            // a type no loader resolves is answered by isAcceptedType(), and inventing a refusal for
            // it here would refuse an encryptedField whose delegate name is misspelled - which no
            // release has refused so far
            return true;
        }

        // a type that normalizes nothing stores nothing, which the column takes
        if (!$fieldDefinition instanceof NormalizerInterface) {
            return true;
        }

        return $this->isScalarCompatible(
            (new ReflectionMethod($fieldDefinition, 'normalize'))->getReturnType()
        );
    }

    private function isScalarCompatible(?ReflectionType $returnType): bool
    {
        if ($returnType === null) {
            return true;
        }

        if ($returnType instanceof ReflectionNamedType) {
            return in_array($returnType->getName(), self::SCALAR_RETURN_TYPES, true);
        }

        if ($returnType instanceof ReflectionUnionType) {
            foreach ($returnType->getTypes() as $member) {
                if (!$this->isScalarCompatible($member)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }
}
