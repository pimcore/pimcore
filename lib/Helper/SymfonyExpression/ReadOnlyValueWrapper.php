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

namespace Pimcore\Helper\SymfonyExpression;

use Carbon\CarbonPeriod;
use Closure;
use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use DateTimeInterface;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\Block;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Classificationstore\DefinitionCache;
use Pimcore\Model\DataObject\Classificationstore\Service as ClassificationstoreService;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\AbstractQuantityValue;
use Pimcore\Model\DataObject\Data\BlockElement;
use Pimcore\Model\DataObject\Data\CalculatedValue;
use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\DataObject\Data\ImageGallery;
use Pimcore\Model\DataObject\Data\Link;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\DataObject\Data\RgbaColor;
use Pimcore\Model\DataObject\Data\StructuredTable;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData as FieldcollectionItem;
use Pimcore\Model\DataObject\Fieldcollection\Definition as FieldcollectionDefinition;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\DataObject\Objectbrick;
use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData as ObjectbrickItem;
use Pimcore\Model\DataObject\Objectbrick\Definition as ObjectbrickDefinition;
use Pimcore\Model\DataObject\QuantityValue\Unit;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service as ElementService;
use Pimcore\Normalizer\NormalizerInterface;
use Stringable;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use WeakMap;
use WeakReference;

/**
 * Turns the values an expression works with into read-only stand-ins, so the
 * expression can read the data of the object graph but never call anything else on it
 * (GHSA-93jv-p24f-gcpp):
 *
 * - elements and data containers become a ReadOnlyObjectProxy that exposes only the
 *   getters of the fields their definition declares, plus a fixed set of element getters
 * - other field values become a ReadOnlyNormalizedView of what the field definition's
 *   normalize() returns, with a few derived getters (e.g. the unit of a quantity value)
 * - dates become detached DateTimeImmutable copies
 * - objects a ReadOnlyValueAdapterInterface supports (security users and tokens built in) become a
 *   ReadOnlyObjectProxy that exposes only the getters of the adapter
 * - any other object becomes a ReadOnlyNormalizedView without getters
 *
 * Calls are resolved lazily, so only the fields an expression reads are loaded and normalized.
 *
 * @internal
 */
final class ReadOnlyValueWrapper
{
    /**
     * Getters every element exposes, in lower case. Field getters are taken from the definition.
     */
    private const ELEMENT_GETTERS = [
        'getid',
        'getkey',
        'getpath',
        'getfullpath',
        'getrealpath',
        'getrealfullpath',
        'getparentid',
        'getparent',
        'gettype',
        'getcreationdate',
        'getmodificationdate',
        'getuserowner',
        'getusermodification',
        'getproperty',
        'hasproperty',
        'getversioncount',
        'ispublished',
        'getpublished',
        'getindex',
        'getclassid',
        'getclassname',
        'getfilename',
        'getmimetype',
        'getfilesize',
        'getmetadata',
        'getwidth',
        'getheight',
        'gettitle',
        'getdescription',
        'getprettyurl',
    ];

    private const ELEMENT_REFERENCE_TYPES = ['asset', 'document', 'object'];

    /**
     * The original value behind every stand-in, handed back to expression functions.
     *
     * @var WeakMap<object, mixed>
     */
    private WeakMap $originals;

    /**
     * The stand-in of every original value, so that an original a function returns (e.g. `max()`)
     * goes back to the expression as its stand-in. Weak references, as the stand-in holds the original.
     *
     * @var WeakMap<object, WeakReference<object>>
     */
    private WeakMap $standIns;

    /**
     * Field getters per class / brick / fieldcollection definition, keyed by lower-case getter name.
     *
     * @var WeakMap<object, array<string, Data>>
     */
    private WeakMap $fieldGetters;

    /**
     * @var ReadOnlyValueAdapterInterface[]
     */
    private array $valueAdapters;

    /**
     * @param iterable<ReadOnlyValueAdapterInterface> $valueAdapters
     */
    public function __construct(iterable $valueAdapters = [])
    {
        $this->originals = new WeakMap();
        $this->standIns = new WeakMap();
        $this->fieldGetters = new WeakMap();
        $this->valueAdapters = [...$valueAdapters, new SecurityValueAdapter()];
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>
     */
    public function wrapVariables(array $values): array
    {
        return array_map(fn (mixed $value): mixed => $this->wrap($value), $values);
    }

    /**
     * Expression functions are registered by the application and may return anything; elements and
     * objects a value adapter supports are wrapped like any other value the expression can reach,
     * also inside returned arrays. An original the function got through a stand-in goes back as that
     * stand-in.
     */
    public function wrapFunctionResult(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->wrapFunctionResult($item), $value);
        }

        $standIn = is_object($value) && isset($this->standIns[$value]) ? $this->standIns[$value]->get() : null;
        if ($standIn !== null) {
            return $standIn;
        }

        if ($value instanceof ElementInterface || $this->isSupportedByValueAdapter($value)) {
            return $this->wrap($value);
        }

        return $value;
    }

    /**
     * Replaces stand-ins with the original values, for expression functions and for the result
     * of an expression.
     */
    public function unwrap(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->unwrap($item), $value);
        }

        if (is_object($value) && isset($this->originals[$value])) {
            return $this->originals[$value];
        }

        return $value;
    }

    /**
     * @throws SyntaxError
     */
    private function wrap(mixed $value, ?Data $fieldDefinition = null, ?Concrete $owner = null): mixed
    {
        if ($value === null || is_scalar($value)) {
            return $value;
        }

        if ($value instanceof ReadOnlyObjectProxy || $value instanceof ReadOnlyNormalizedView) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        if (is_object($value)) {
            $proxy = $this->createProxy($value);
            if ($proxy !== null) {
                $this->registerStandIn($proxy, $value);

                return $proxy;
            }
        }

        if (is_array($value) && $fieldDefinition instanceof Block) {
            return $this->wrapBlock($value, $fieldDefinition, $owner);
        }

        if (is_array($value)
            && ($fieldDefinition instanceof AdvancedManyToManyRelation
                || $fieldDefinition instanceof AdvancedManyToManyObjectRelation)
        ) {
            return $this->wrapRelationMetadata($value);
        }

        if (is_array($value) && !$this->containsObjectsOtherThanElements($value)) {
            return array_map(fn (mixed $item): mixed => $this->wrap($item), $value);
        }

        if ($fieldDefinition instanceof NormalizerInterface) {
            $normalized = $fieldDefinition->normalize($value, ['object' => $owner, 'fieldDefinition' => $fieldDefinition]);

            return $this->wrapNormalized($normalized, is_object($value) ? $value : null);
        }

        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->wrap($item), $value);
        }

        // a value object without a field definition, e.g. inside asset metadata
        return $this->wrapNormalized($this->normalizeWithoutDefinition($value), $value);
    }

    private function createProxy(object $value): ?ReadOnlyObjectProxy
    {
        $methodResolver = match (true) {
            $value instanceof ElementInterface => $this->createElementResolver($value),
            $value instanceof Localizedfield => $this->createLocalizedfieldResolver($value),
            $value instanceof Objectbrick => $this->createObjectbrickResolver($value),
            $value instanceof ObjectbrickItem => $this->createFieldOwnerResolver(
                $value,
                $value->getDefinition(),
                $value->getObject(),
                ['gettype', 'getfieldname']
            ),
            $value instanceof Fieldcollection => $this->createFieldcollectionResolver($value),
            $value instanceof FieldcollectionItem => $this->createFieldOwnerResolver(
                $value,
                $value->getDefinition(),
                $value->getObject(),
                ['gettype', 'getindex', 'getfieldname']
            ),
            $value instanceof Classificationstore => $this->createClassificationstoreResolver($value),
            default => $this->createAdapterResolver($value),
        };

        if ($methodResolver === null) {
            return null;
        }

        return new ReadOnlyObjectProxy($methodResolver, $this->createStringResolver($value));
    }

    /**
     * @return Closure(string, array<mixed>): mixed
     */
    private function createElementResolver(ElementInterface $element): Closure
    {
        return function (string $method, array $arguments) use ($element): mixed {
            if ($element instanceof Concrete) {
                $fieldDefinition = $this->getFieldGetters($element->getClass())[strtolower($method)] ?? null;
                if ($fieldDefinition !== null) {
                    return $this->wrap(
                        $element->$method(...$this->scalarArguments($method, $arguments)),
                        $fieldDefinition,
                        $element
                    );
                }
            }

            if (in_array(strtolower($method), self::ELEMENT_GETTERS, true) && method_exists($element, $method)) {
                return $this->wrap($element->$method(...$this->scalarArguments($method, $arguments)));
            }

            throw $this->createNotAvailableError($method, $element);
        };
    }

    /**
     * @return null|Closure(string, array<mixed>): mixed
     */
    private function createAdapterResolver(object $value): ?Closure
    {
        foreach ($this->valueAdapters as $valueAdapter) {
            if (!$valueAdapter->supports($value)) {
                continue;
            }

            $getters = $valueAdapter->getGetters($value);

            return function (string $method, array $arguments) use ($getters, $value): mixed {
                $getter = $getters[strtolower($method)] ?? null;
                if ($getter === null) {
                    throw $this->createNotAvailableError($method, $value);
                }

                return $this->wrap($getter($this->scalarArguments($method, $arguments)));
            };
        }

        return null;
    }

    /**
     * Object brick and fieldcollection items: the getters of their definition's fields.
     *
     * @param string[] $itemGetters lower-case getters of the item itself
     *
     * @return Closure(string, array<mixed>): mixed
     */
    private function createFieldOwnerResolver(
        ObjectbrickItem|FieldcollectionItem $item,
        ObjectbrickDefinition|FieldcollectionDefinition $definition,
        ?Concrete $owner,
        array $itemGetters
    ): Closure {
        return function (string $method, array $arguments) use ($item, $definition, $owner, $itemGetters): mixed {
            $fieldDefinition = $this->getFieldGetters($definition)[strtolower($method)] ?? null;
            if ($fieldDefinition !== null) {
                return $this->wrap(
                    $item->$method(...$this->scalarArguments($method, $arguments)),
                    $fieldDefinition,
                    $owner
                );
            }

            if (in_array(strtolower($method), $itemGetters, true)) {
                return $this->wrap($item->$method());
            }

            throw $this->createNotAvailableError($method, $item);
        };
    }

    /**
     * @return Closure(string, array<mixed>): mixed
     */
    private function createLocalizedfieldResolver(Localizedfield $localizedfield): Closure
    {
        return function (string $method, array $arguments) use ($localizedfield): mixed {
            if (strtolower($method) !== 'getlocalizedvalue') {
                throw $this->createNotAvailableError($method, $localizedfield);
            }

            $arguments = $this->scalarArguments($method, $arguments);
            $fieldDefinition = $localizedfield->getFieldDefinition(
                (string) ($arguments[0] ?? ''),
                $localizedfield->getContext()
            );
            if ($fieldDefinition === null) {
                throw new SyntaxError(sprintf(
                    'Unknown localized field `%s` in expression',
                    (string) ($arguments[0] ?? '')
                ));
            }

            return $this->wrap(
                $localizedfield->getLocalizedValue(...$arguments),
                $fieldDefinition,
                $localizedfield->getObject()
            );
        };
    }

    /**
     * @return Closure(string, array<mixed>): mixed
     */
    private function createObjectbrickResolver(Objectbrick $objectbrick): Closure
    {
        return function (string $method, array $arguments) use ($objectbrick): mixed {
            $lowerMethod = strtolower($method);
            if ($lowerMethod === 'getitems') {
                return $this->wrap($objectbrick->getItems());
            }

            foreach ($objectbrick->getAllowedBrickTypes() as $brickType) {
                if ($lowerMethod === 'get' . strtolower($brickType)) {
                    return $this->wrap($objectbrick->{'get' . ucfirst($brickType)}());
                }
            }

            throw $this->createNotAvailableError($method, $objectbrick);
        };
    }

    /**
     * @return Closure(string, array<mixed>): mixed
     */
    private function createFieldcollectionResolver(Fieldcollection $fieldcollection): Closure
    {
        return function (string $method, array $arguments) use ($fieldcollection): mixed {
            return match (strtolower($method)) {
                'get' => $this->wrap($fieldcollection->get((int) ($this->scalarArguments($method, $arguments)[0] ?? 0))),
                'getitems' => $this->wrap($fieldcollection->getItems()),
                default => throw $this->createNotAvailableError($method, $fieldcollection),
            };
        };
    }

    /**
     * @return Closure(string, array<mixed>): mixed
     */
    private function createClassificationstoreResolver(Classificationstore $classificationstore): Closure
    {
        return function (string $method, array $arguments) use ($classificationstore): mixed {
            $lowerMethod = strtolower($method);
            if ($lowerMethod === 'getactivegroups') {
                return $this->wrap($classificationstore->getActiveGroups());
            }

            if ($lowerMethod !== 'getlocalizedkeyvalue') {
                throw $this->createNotAvailableError($method, $classificationstore);
            }

            $arguments = $this->scalarArguments($method, $arguments);
            $keyConfig = DefinitionCache::get((int) ($arguments[1] ?? 0));

            return $this->wrap(
                $classificationstore->getLocalizedKeyValue(...$arguments),
                $keyConfig ? ClassificationstoreService::getFieldDefinitionFromKeyConfig($keyConfig) : null,
                $classificationstore->getObject()
            );
        };
    }

    /**
     * @return array<string, Data>
     */
    private function getFieldGetters(
        ClassDefinition|ObjectbrickDefinition|FieldcollectionDefinition $definition
    ): array {
        if (!isset($this->fieldGetters[$definition])) {
            $getters = [];
            foreach ($definition->getFieldDefinitions() as $fieldDefinition) {
                $getters['get' . strtolower($fieldDefinition->getName())] = $fieldDefinition;

                // localized fields have getters on the owner, e.g. getName($language)
                if ($fieldDefinition instanceof Localizedfields) {
                    foreach ($fieldDefinition->getFieldDefinitions() as $localizedFieldDefinition) {
                        $getters['get' . strtolower($localizedFieldDefinition->getName())] = $localizedFieldDefinition;
                    }
                }
            }

            $this->fieldGetters[$definition] = $getters;
        }

        return $this->fieldGetters[$definition];
    }

    /**
     * @param array<array<string, mixed>> $value
     *
     * @return list<ReadOnlyNormalizedView>
     */
    private function wrapBlock(array $value, Block $fieldDefinition, ?Concrete $owner): array
    {
        $items = [];
        foreach ($value as $blockItem) {
            $itemData = [];
            foreach ($blockItem as $key => $blockElement) {
                if (!$blockElement instanceof BlockElement) {
                    continue;
                }

                $itemData[$key] = $this->createView([
                    'name' => $blockElement->getName(),
                    'type' => $blockElement->getType(),
                    'data' => $this->wrap(
                        $blockElement->getData(),
                        $fieldDefinition->getFieldDefinition((string) $key),
                        $owner
                    ),
                ], $blockElement);
            }

            $items[] = $this->createView($itemData, null);
        }

        return $items;
    }

    /**
     * Relation metadata: the related element, the fieldname, the columns and one getter per column.
     *
     * @param array<mixed> $value
     *
     * @return list<ReadOnlyNormalizedView>
     */
    private function wrapRelationMetadata(array $value): array
    {
        $items = [];
        foreach ($value as $metadata) {
            if (!$metadata instanceof ElementMetadata && !$metadata instanceof ObjectMetadata) {
                continue;
            }

            $element = $this->wrap($metadata->getElement());
            $columnData = array_map(fn (mixed $column): mixed => $this->wrap($column), $metadata->getData());

            $itemData = array_merge($columnData, [
                'element' => $element,
                'fieldname' => $metadata->getFieldname(),
                'columns' => $metadata->getColumns(),
                'data' => $columnData,
            ]);
            if ($metadata instanceof ObjectMetadata) {
                $itemData['object'] = $element;
            }

            $items[] = $this->createView($itemData, $metadata);
        }

        return $items;
    }

    /**
     * Wraps normalized data: element references are resolved to elements, lists of scalars stay
     * arrays (so that operators like `in` keep working), everything else becomes a view.
     */
    private function wrapNormalized(mixed $normalized, ?object $original): mixed
    {
        if ($normalized === null || is_scalar($normalized)) {
            return $normalized;
        }

        if (is_object($normalized)) {
            return $this->wrap($normalized);
        }

        if (!is_array($normalized)) {
            return null;
        }

        if ($this->isElementReference($normalized)) {
            return $this->wrap(ElementService::getElementById($normalized['type'], $normalized['id']));
        }

        if ($original === null && array_is_list($normalized) && !$this->containsNonScalars($normalized)) {
            return $normalized;
        }

        return $this->createView($normalized, $original);
    }

    /**
     * @param array<array-key, mixed> $data
     */
    private function createView(array $data, ?object $original): ReadOnlyNormalizedView
    {
        $view = new ReadOnlyNormalizedView(
            $data,
            fn (mixed $item): mixed => $this->wrapNormalized($item, null),
            $original !== null ? $this->createDerivedGetters($original, $data) : [],
            $this->createStringResolver($original ?? $data)
        );

        if ($original !== null) {
            $this->registerStandIn($view, $original);
        }

        return $view;
    }

    /**
     * Getters of value objects whose result is not part of the normalized data.
     *
     * @param array<array-key, mixed> $normalized
     *
     * @return array<string, Closure(array<mixed>): mixed>
     */
    private function createDerivedGetters(object $value, array $normalized): array
    {
        if ($value instanceof AbstractQuantityValue) {
            return [
                'getunit' => fn (): mixed => $this->wrapUnit($value->getUnit()),
            ];
        }

        if ($value instanceof Link) {
            return [
                'gethref' => fn (): string => $value->getHref(),
                'getpath' => fn (): string => $value->getPath(),
                'getelement' => fn (): mixed => $this->wrap($value->getElement()),
            ];
        }

        if ($value instanceof RgbaColor) {
            return [
                'gethex' => fn (array $arguments): string => $value->getHex(
                    ...$this->scalarArguments('getHex', $arguments)
                ),
                'getrgb' => fn (): array => $value->getRgb(),
                'getrgba' => fn (): array => $value->getRgba(),
                'getcssrgba' => fn (): array => $value->getCssRgba(),
            ];
        }

        if ($value instanceof ImageGallery) {
            return [
                'getitems' => fn (): mixed => $this->wrapNormalized($normalized, null),
            ];
        }

        if ($value instanceof StructuredTable) {
            return [
                'getdata' => fn (): mixed => $this->wrapNormalized($normalized, null),
            ];
        }

        return [];
    }

    private function wrapUnit(?Unit $unit, bool $withBaseUnit = true): ?ReadOnlyNormalizedView
    {
        if ($unit === null) {
            return null;
        }

        return $this->createView([
            'id' => $unit->getId(),
            'abbreviation' => $unit->getAbbreviation(),
            'longname' => $unit->getLongname(),
            'group' => $unit->getGroup(),
            'factor' => $unit->getFactor(),
            'conversionOffset' => $unit->getConversionOffset(),
            'reference' => $unit->getReference(),
            'baseunit' => $withBaseUnit ? $this->wrapUnit($unit->getBaseunit(), false) : null,
        ], null);
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeWithoutDefinition(object $value): array
    {
        if ($value instanceof CalculatedValue) {
            return [
                'fieldname' => $value->getFieldname(),
                'index' => $value->getIndex(),
                'ownerName' => $value->getOwnerName(),
                'ownerType' => $value->getOwnerType(),
                'position' => $value->getPosition(),
                'groupId' => $value->getGroupId(),
                'keyId' => $value->getKeyId(),
                'calculatorData' => $value->getCalculatorData(),
            ];
        }

        if ($value instanceof DatePeriod || $value instanceof CarbonPeriod) {
            return [
                'startDate' => $value->getStartDate(),
                'endDate' => $value->getEndDate(),
            ];
        }

        if ($value instanceof DateInterval) {
            return [
                'y' => $value->y,
                'm' => $value->m,
                'd' => $value->d,
                'h' => $value->h,
                'i' => $value->i,
                's' => $value->s,
                'invert' => $value->invert,
                'days' => $value->days,
            ];
        }

        return [];
    }

    /**
     * @return Closure(): string
     */
    private function createStringResolver(mixed $value): Closure
    {
        return static function () use ($value): string {
            if ($value instanceof Stringable) {
                return (string) $value;
            }

            throw new SyntaxError(sprintf(
                '`%s` cannot be converted to a string in expressions',
                get_debug_type($value)
            ));
        };
    }

    /**
     * @param array<mixed> $arguments
     *
     * @return array<mixed>
     *
     * @throws SyntaxError
     */
    private function scalarArguments(string $method, array $arguments): array
    {
        foreach ($arguments as $argument) {
            if ($argument !== null && !is_scalar($argument)) {
                throw new SyntaxError(sprintf(
                    'Only scalar arguments can be passed to `%s()` in expressions',
                    $method
                ));
            }
        }

        return $arguments;
    }

    private function registerStandIn(object $standIn, object $original): void
    {
        $this->originals[$standIn] = $original;
        $this->standIns[$original] = WeakReference::create($standIn);
    }

    private function isSupportedByValueAdapter(mixed $value): bool
    {
        if (!is_object($value)) {
            return false;
        }

        foreach ($this->valueAdapters as $valueAdapter) {
            if ($valueAdapter->supports($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<mixed> $value
     */
    private function isElementReference(array $value): bool
    {
        return count($value) === 2
            && in_array($value['type'] ?? null, self::ELEMENT_REFERENCE_TYPES, true)
            && is_numeric($value['id'] ?? null);
    }

    /**
     * @param array<mixed> $value
     */
    private function containsObjectsOtherThanElements(array $value): bool
    {
        foreach ($value as $item) {
            if (is_array($item) || (is_object($item) && !$item instanceof ElementInterface)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<mixed> $value
     */
    private function containsNonScalars(array $value): bool
    {
        foreach ($value as $item) {
            if ($item !== null && !is_scalar($item)) {
                return true;
            }
        }

        return false;
    }

    private function createNotAvailableError(string $method, object $receiver): SyntaxError
    {
        return new SyntaxError(sprintf(
            '`%s()` is not available on `%s` in expressions',
            $method,
            get_debug_type($receiver)
        ));
    }
}
