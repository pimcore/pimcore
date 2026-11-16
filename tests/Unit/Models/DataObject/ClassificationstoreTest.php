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

namespace Pimcore\Tests\Unit\Model\DataObject;

use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\MultiValue as MultiValueKey;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Classificationstore\DefinitionCache;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\Service;
use Pimcore\Model\DataObject\Data\MultiValue;
use Pimcore\Model\Exception\InvalidConfigException;
use Pimcore\Tests\Support\Test\TestCase;
use ReflectionMethod;

class ClassificationstoreTest extends TestCase
{
    private const GROUP_ID = 910001;

    private const PLAIN_KEY_ID = 920001;

    private const POSITIONED_KEY_ID = 920002;

    private const VARIANT_KEY_ID = 920003;

    private const IMPERIAL = 'imperial';

    private const METRIC = 'metric';

    protected function tearDown(): void
    {
        DefinitionCache::clear(null);
        Service::clearDefinitionsCache();

        parent::tearDown();
    }

    /**
     * Regression: the recursive call inside mergeArrays() was passing arguments
     * in the wrong order ($a2[$key], $value) instead of ($value, $a2[$key]),
     * which flipped the merge precedence for nested arrays so that parent[0]
     * overwrote child[0] in numeric arrays.
     *
     * Realistic Classification Store structure: groupId → keyId → locale/default.
     */
    public function testMergeArraysPreservesChildMultiselectValues(): void
    {
        $store = new Classificationstore();

        $method = new ReflectionMethod($store, 'mergeArrays');
        $method->setAccessible(true);

        // Simulate: $a1 = child (accumulated fieldsArray), $a2 = parent items
        // Child should win on conflicts
        $child = [
            1 => [  // groupId
                100 => [  // keyId
                    'default' => ['Breathable', 'Coated', 'Seamless'],
                ],
            ],
        ];

        $parent = [
            1 => [
                100 => [
                    'default' => ['Abrasion-Resistant'],
                ],
            ],
        ];

        $result = $method->invoke($store, $child, $parent);

        // Child values should be preserved, not overwritten by parent
        $this->assertSame(
            ['Breathable', 'Coated', 'Seamless'],
            $result[1][100]['default'],
            'Child multiselect values should not be overwritten by parent values during inheritance merge'
        );
    }

    public function testMergeArraysChildInheritsParentOnlyKeys(): void
    {
        $store = new Classificationstore();

        $method = new ReflectionMethod($store, 'mergeArrays');
        $method->setAccessible(true);

        // Child has one key, parent has a different key — both should appear
        $child = [
            1 => [
                100 => ['default' => ['Red']],
            ],
        ];

        $parent = [
            1 => [
                200 => ['default' => ['Steel']],
            ],
        ];

        $result = $method->invoke($store, $child, $parent);

        $this->assertSame(['Red'], $result[1][100]['default']);
        $this->assertSame(['Steel'], $result[1][200]['default']);
    }

    /**
     * An absent qualifier is the default: the entry addresses exactly the slot
     * setLocalizedKeyValue() writes, so the two paths answer one another.
     */
    public function testAnUnqualifiedEntryAddressesTheDefaultLanguageAndVariantAtPositionZero(): void
    {
        $this->seedKey(self::PLAIN_KEY_ID);

        $store = new Classificationstore();
        $store->setKeyValues(self::GROUP_ID, self::PLAIN_KEY_ID, [new MultiValue('plain')]);

        $this->assertSame(['plain'], $store->getKeyValues(self::GROUP_ID, self::PLAIN_KEY_ID));
        $this->assertSame(['plain'], $store->getKeyValues(self::GROUP_ID, self::PLAIN_KEY_ID, 'default', 'default'));
        $this->assertSame('plain', $store->getKeyValue(self::GROUP_ID, self::PLAIN_KEY_ID));
        $this->assertSame(
            'plain',
            $store->getLocalizedKeyValue(self::GROUP_ID, self::PLAIN_KEY_ID),
            'the documented getter keeps answering position 0 of the default variant'
        );
        $this->assertSame(
            'plain',
            $store->getItems()[self::GROUP_ID][self::PLAIN_KEY_ID]['default'],
            'getItems() keeps its documented shape with that one value at the leaf'
        );
    }

    /**
     * A value stored above position 0 - put on the model the way an engine restores it from a
     * document written earlier - is kept and not read: the slot answers position 0 alone.
     */
    public function testAValueStoredAbovePositionZeroIsKeptAndNotRead(): void
    {
        $this->seedKey(self::POSITIONED_KEY_ID, $this->variantKey());

        $store = new Classificationstore();
        $store->setQualifiedKeyValue(self::GROUP_ID, self::POSITIONED_KEY_ID, 'first');
        $store->setQualifiedKeyValue(self::GROUP_ID, self::POSITIONED_KEY_ID, 'second', null, null, 1);
        $store->setQualifiedKeyValue(self::GROUP_ID, self::POSITIONED_KEY_ID, '1.8 m', null, self::METRIC);
        $store->setQualifiedKeyValue(self::GROUP_ID, self::POSITIONED_KEY_ID, '1.9 m', null, self::METRIC, 1);

        $this->assertSame(['first'], $store->getKeyValues(self::GROUP_ID, self::POSITIONED_KEY_ID));
        $this->assertSame(
            ['1.8 m'],
            $store->getKeyValues(self::GROUP_ID, self::POSITIONED_KEY_ID, null, self::METRIC)
        );
        $this->assertNull($store->getKeyValue(self::GROUP_ID, self::POSITIONED_KEY_ID, null, null, 1));
        $this->assertNull($store->getKeyValue(self::GROUP_ID, self::POSITIONED_KEY_ID, null, self::METRIC, 1));

        $qualified = $store->getQualifiedItems()[self::GROUP_ID][self::POSITIONED_KEY_ID]['default'];
        $this->assertSame('second', $qualified['default'][1], 'the value above position 0 is still held');
        $this->assertSame('1.9 m', $qualified[self::METRIC][1]);
    }

    /**
     * The read answers the value addressed by position 0, not the first in the list: a slot holding
     * only a higher position answers nothing, instead of that value moving up into position 0.
     */
    public function testASlotHoldingOnlyAHigherPositionAnswersNothing(): void
    {
        $this->seedKey(self::POSITIONED_KEY_ID, $this->variantKey());

        $store = new Classificationstore();
        $store->setQualifiedKeyValue(self::GROUP_ID, self::POSITIONED_KEY_ID, 'blue', null, null, 1);
        $store->setQualifiedKeyValue(self::GROUP_ID, self::POSITIONED_KEY_ID, 'green', null, null, 2);
        $store->setQualifiedKeyValue(self::GROUP_ID, self::POSITIONED_KEY_ID, '1.9 m', null, self::METRIC, 1);

        $this->assertSame([], $store->getKeyValues(self::GROUP_ID, self::POSITIONED_KEY_ID));
        $this->assertNull($store->getKeyValue(self::GROUP_ID, self::POSITIONED_KEY_ID));
        $this->assertSame([], $store->getKeyValues(self::GROUP_ID, self::POSITIONED_KEY_ID, null, self::METRIC));
        $this->assertNull($store->getLocalizedKeyValue(self::GROUP_ID, self::POSITIONED_KEY_ID));
    }

    /**
     * A write addresses position 0 and nothing else, so a value stored above it is not the write's
     * to remove - clearing the slot would delete it on the next save.
     */
    public function testWritingPositionZeroKeepsTheValueStoredAboveIt(): void
    {
        $this->seedKey(self::POSITIONED_KEY_ID, $this->variantKey());

        $store = new Classificationstore();
        $store->setQualifiedKeyValue(self::GROUP_ID, self::POSITIONED_KEY_ID, 'first');
        $store->setQualifiedKeyValue(self::GROUP_ID, self::POSITIONED_KEY_ID, 'second', null, null, 1);
        $store->setQualifiedKeyValue(self::GROUP_ID, self::POSITIONED_KEY_ID, '1.80 m', null, self::METRIC);
        $store->setQualifiedKeyValue(self::GROUP_ID, self::POSITIONED_KEY_ID, '1.81 m', null, self::METRIC, 1);

        $store->setKeyValues(self::GROUP_ID, self::POSITIONED_KEY_ID, [
            new MultiValue('edited'),
            new MultiValue('1.82 m', null, self::METRIC),
        ]);

        $this->assertSame(['edited'], $store->getKeyValues(self::GROUP_ID, self::POSITIONED_KEY_ID));
        $this->assertSame(
            ['1.82 m'],
            $store->getKeyValues(self::GROUP_ID, self::POSITIONED_KEY_ID, null, self::METRIC)
        );

        $qualified = $store->getQualifiedItems()[self::GROUP_ID][self::POSITIONED_KEY_ID]['default'];
        $this->assertSame('edited', $qualified['default'][0]);
        $this->assertSame('second', $qualified['default'][1], 'the value above position 0 survived the write');
        $this->assertSame('1.82 m', $qualified[self::METRIC][0]);
        $this->assertSame('1.81 m', $qualified[self::METRIC][1]);
    }

    /**
     * Every variant of a key is readable and writable on its own: writing one does not disturb
     * what another holds.
     */
    public function testWritingOneVariantLeavesTheOtherAsItWas(): void
    {
        $this->seedKey(self::VARIANT_KEY_ID, $this->variantKey());

        $store = new Classificationstore();
        $store->setKeyValues(self::GROUP_ID, self::VARIANT_KEY_ID, [
            new MultiValue('1.8 m', null, self::METRIC),
            new MultiValue('5 ft 11 in', null, self::IMPERIAL),
        ]);

        $store->setKeyValues(self::GROUP_ID, self::VARIANT_KEY_ID, [
            new MultiValue('6 ft', null, self::IMPERIAL),
        ]);

        $this->assertSame(
            ['1.8 m'],
            $store->getKeyValues(self::GROUP_ID, self::VARIANT_KEY_ID, null, self::METRIC)
        );
        $this->assertSame(
            ['6 ft'],
            $store->getKeyValues(self::GROUP_ID, self::VARIANT_KEY_ID, null, self::IMPERIAL)
        );
    }

    /**
     * One emptiness rule for every slot: an entry carrying no value clears the slot it addresses.
     * The default slot is the only one that ever did, so a value under a variant could not be
     * removed at all - and the slot read back as holding one value, which is not the same thing as
     * holding none.
     */
    public function testAnEntryWithoutAValueClearsTheSlotItAddresses(): void
    {
        $this->seedKey(self::VARIANT_KEY_ID, $this->variantKey());

        $store = new Classificationstore();
        $store->setKeyValues(self::GROUP_ID, self::VARIANT_KEY_ID, [
            new MultiValue('1.8 m', null, self::METRIC),
            new MultiValue('tall'),
        ]);

        $store->setKeyValues(self::GROUP_ID, self::VARIANT_KEY_ID, [
            new MultiValue(null, null, self::METRIC),
        ]);
        $store->setKeyValues(self::GROUP_ID, self::VARIANT_KEY_ID, [
            new MultiValue(null),
        ]);

        $this->assertSame([], $store->getKeyValues(self::GROUP_ID, self::VARIANT_KEY_ID, null, self::METRIC));
        $this->assertSame([], $store->getKeyValues(self::GROUP_ID, self::VARIANT_KEY_ID));
        $this->assertSame(
            [],
            $store->getQualifiedItems(),
            'no slot was left holding the absence of a value'
        );
    }

    /**
     * "0" is a value, and it is one in every slot - the rule the default slot has always applied is
     * the rule the others now apply, which includes the values PHP would otherwise make disappear.
     */
    public function testZeroIsAValueInEverySlot(): void
    {
        $this->seedKey(self::VARIANT_KEY_ID, $this->variantKey());

        $store = new Classificationstore();
        $store->setKeyValues(self::GROUP_ID, self::VARIANT_KEY_ID, [
            new MultiValue('0'),
            new MultiValue('0', null, self::METRIC),
        ]);

        $this->assertSame(['0'], $store->getKeyValues(self::GROUP_ID, self::VARIANT_KEY_ID));
        $this->assertSame(
            ['0'],
            $store->getKeyValues(self::GROUP_ID, self::VARIANT_KEY_ID, null, self::METRIC)
        );
    }

    /**
     * Absent qualifiers normalize to the default before the comparison, so an entry with no
     * position and one at position 0 are the same slot - and so is an entry with no variant beside
     * one naming the default variant.
     *
     * @dataProvider collidingEntriesProvider
     *
     * @param MultiValue[] $entries
     */
    public function testTwoEntriesAddressingOneSlotAreRefusedAndNothingIsStored(array $entries): void
    {
        $this->seedKey(self::PLAIN_KEY_ID);

        $store = new Classificationstore();
        $store->setKeyValues(self::GROUP_ID, self::PLAIN_KEY_ID, [new MultiValue('stored')]);

        try {
            $store->setKeyValues(self::GROUP_ID, self::PLAIN_KEY_ID, $entries);
            $this->fail('two entries addressing one slot have to be refused');
        } catch (InvalidConfigException $exception) {
            $this->assertStringContainsString('same slot', $exception->getMessage());
        }

        $this->assertSame(
            ['stored'],
            $store->getKeyValues(self::GROUP_ID, self::PLAIN_KEY_ID),
            'the refusal is not partial'
        );
    }

    /**
     * @return array<string, array{MultiValue[]}>
     */
    public static function collidingEntriesProvider(): array
    {
        return [
            'no position beside position 0' => [[new MultiValue(1), new MultiValue(2, null, null, 0)]],
            'no variant beside the default variant' => [
                [new MultiValue(1), new MultiValue(2, null, 'default')],
            ],
            'no language beside the default language' => [
                [new MultiValue(1), new MultiValue(2, 'default')],
            ],
        ];
    }

    public function testAVariantTheKeyDoesNotDeclareIsRefusedAndNothingIsStored(): void
    {
        $this->seedKey(self::VARIANT_KEY_ID, $this->variantKey([self::IMPERIAL]));

        $store = new Classificationstore();
        $store->setKeyValues(self::GROUP_ID, self::VARIANT_KEY_ID, [
            new MultiValue('stored', null, self::IMPERIAL),
        ]);

        try {
            $store->setKeyValues(self::GROUP_ID, self::VARIANT_KEY_ID, [
                new MultiValue('refused', null, self::METRIC),
            ]);
            $this->fail('an undeclared variant has to be refused');
        } catch (InvalidConfigException $exception) {
            $this->assertStringContainsString(self::METRIC, $exception->getMessage());
        }

        $this->assertSame(
            ['stored'],
            $store->getKeyValues(self::GROUP_ID, self::VARIANT_KEY_ID, null, self::IMPERIAL)
        );
        $this->assertSame([], $store->getKeyValues(self::GROUP_ID, self::VARIANT_KEY_ID, null, self::METRIC));
    }

    public function testAKeyDeclaringNoVariantRefusesANamedOneAndKeepsWhatItHolds(): void
    {
        $this->seedKey(self::PLAIN_KEY_ID);

        $store = new Classificationstore();
        $store->setKeyValues(self::GROUP_ID, self::PLAIN_KEY_ID, [new MultiValue('stored')]);

        try {
            $store->setKeyValues(self::GROUP_ID, self::PLAIN_KEY_ID, [
                new MultiValue('refused', null, self::METRIC),
            ]);
            $this->fail('a key declaring no variant has to refuse a named one');
        } catch (InvalidConfigException $exception) {
            $this->assertStringContainsString(self::METRIC, $exception->getMessage());
        }

        $this->assertSame(['stored'], $store->getKeyValues(self::GROUP_ID, self::PLAIN_KEY_ID));
    }

    /**
     * A key that declines the unqualified value refuses the slot of a value naming no variant, and
     * the slot of one naming `default` explicitly, the way it refuses a variant it never declared -
     * and the refusal is not partial: the write that carries a declared variant beside the refused
     * entry stores neither.
     */
    public function testAKeyDecliningTheUnqualifiedValueRefusesItAndKeepsWhatItHolds(): void
    {
        $this->seedKey(self::VARIANT_KEY_ID, $this->decliningKey([self::IMPERIAL]));

        $store = new Classificationstore();
        $store->setKeyValues(self::GROUP_ID, self::VARIANT_KEY_ID, [
            new MultiValue('stored', null, self::IMPERIAL),
        ]);

        foreach ([null, 'default'] as $variant) {
            try {
                // the declared variant comes FIRST, so a partial write replaces what the key holds
                $store->setKeyValues(self::GROUP_ID, self::VARIANT_KEY_ID, [
                    new MultiValue('overwritten', null, self::IMPERIAL),
                    new MultiValue('refused', null, $variant),
                ]);
                $this->fail('a key declining the unqualified value has to refuse it');
            } catch (InvalidConfigException $exception) {
                $this->assertStringContainsString((string) self::VARIANT_KEY_ID, $exception->getMessage());
                $this->assertStringContainsString('"default"', $exception->getMessage());
            }
        }

        $this->assertSame(
            ['stored'],
            $store->getKeyValues(self::GROUP_ID, self::VARIANT_KEY_ID, null, self::IMPERIAL),
            'the refusal is not partial'
        );
        $this->assertSame([], $store->getKeyValues(self::GROUP_ID, self::VARIANT_KEY_ID));
    }

    /**
     * Every key that permits the unqualified value: one declaring it, a definition blob that does
     * not carry the member at all, and a key of another type - which carries no declaration of any
     * kind. The write is accepted on all three and read back as it always was.
     *
     * @dataProvider keysPermittingTheUnqualifiedValueProvider
     */
    public function testAKeyPermittingTheUnqualifiedValueAcceptsItAndReadsItBack(string $case): void
    {
        $keyId = self::PLAIN_KEY_ID;

        if ($case === 'declared') {
            $this->seedKey($keyId, $this->variantKey([self::IMPERIAL]));
        } elseif ($case === 'absent from the blob') {
            $this->seedKeyFromBlob($keyId, ['name' => 'mvKey', 'variants' => [['name' => self::IMPERIAL]]]);
        } else {
            $this->seedKeyOfAnotherType($keyId);
        }

        $store = new Classificationstore();
        $store->setKeyValues(self::GROUP_ID, $keyId, [new MultiValue('stored')]);

        $this->assertSame(['stored'], $store->getKeyValues(self::GROUP_ID, $keyId));
        $this->assertSame('stored', $store->getLocalizedKeyValue(self::GROUP_ID, $keyId));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function keysPermittingTheUnqualifiedValueProvider(): array
    {
        return [
            'the declaration on' => ['declared'],
            'the member absent from the blob' => ['absent from the blob'],
            'a key of another type' => ['another type'],
        ];
    }

    /**
     * And the fourth key with no declaration: one whose configuration is gone, which is what the
     * load path of an engine reaches for a value whose key was deleted. The read answers the stored
     * unqualified value rather than hiding it. It is read here and not written: the legacy write
     * path resolves the key configuration itself and has never been reachable without one.
     */
    public function testAKeyWhoseConfigurationIsGoneStillReadsItsUnqualifiedValue(): void
    {
        $this->seedKey(self::PLAIN_KEY_ID, $this->variantKey([self::IMPERIAL]));

        $store = new Classificationstore();
        $store->setKeyValues(self::GROUP_ID, self::PLAIN_KEY_ID, [new MultiValue('stored')]);

        DefinitionCache::clear(null);
        Service::clearDefinitionsCache();

        $this->assertSame(['stored'], $store->getKeyValues(self::GROUP_ID, self::PLAIN_KEY_ID));
    }

    /**
     * A key holds one value per variant, so every write above position 0 is refused - on the
     * default variant and on a declared one alike - and the refusal is not partial.
     */
    public function testAPositionAboveZeroIsRefusedAndNothingIsStored(): void
    {
        $this->seedKey(self::VARIANT_KEY_ID, $this->variantKey());

        $store = new Classificationstore();
        $store->setKeyValues(self::GROUP_ID, self::VARIANT_KEY_ID, [new MultiValue('stored')]);

        foreach ([null, self::METRIC] as $variant) {
            try {
                $store->setKeyValues(self::GROUP_ID, self::VARIANT_KEY_ID, [
                    new MultiValue('overwritten', null, null, 0),
                    new MultiValue('second', null, $variant, 1),
                ]);
                $this->fail('a position above 0 has to be refused');
            } catch (InvalidConfigException $exception) {
                $this->assertStringContainsString('position 1', $exception->getMessage());
                $this->assertStringNotContainsString('declare a list', $exception->getMessage());
            }
        }

        $this->assertSame(['stored'], $store->getKeyValues(self::GROUP_ID, self::VARIANT_KEY_ID));
        $this->assertSame([], $store->getKeyValues(self::GROUP_ID, self::VARIANT_KEY_ID, null, self::METRIC));
    }

    /**
     * Narrowing a declaration hides values rather than deleting them, so widening it again makes
     * them readable. The stored side of that is a DocumentGeneralTest case; this is the read.
     */
    public function testNarrowingADeclarationHidesTheValuesAndWideningItAgainShowsThem(): void
    {
        $this->seedKey(self::VARIANT_KEY_ID, $this->variantKey());

        $store = new Classificationstore();
        $store->setKeyValues(self::GROUP_ID, self::VARIANT_KEY_ID, [
            new MultiValue('1.8 m', null, self::METRIC),
            new MultiValue('5 ft 11 in', null, self::IMPERIAL),
            new MultiValue('tall'),
        ]);

        $this->seedKey(self::VARIANT_KEY_ID, $this->variantKey([self::METRIC]));

        $this->assertSame([], $store->getKeyValues(self::GROUP_ID, self::VARIANT_KEY_ID, null, self::IMPERIAL));
        $this->assertSame(['tall'], $store->getKeyValues(self::GROUP_ID, self::VARIANT_KEY_ID));

        $this->seedKey(self::VARIANT_KEY_ID, $this->variantKey());

        $this->assertSame(
            ['5 ft 11 in'],
            $store->getKeyValues(self::GROUP_ID, self::VARIANT_KEY_ID, null, self::IMPERIAL)
        );
    }

    /**
     * The documented items and the qualified items are two readers of one store, and they have to
     * agree about the one value both describe: position 0 of the default variant. getItems() is
     * what every engine wrote before a value could carry qualifiers, so a disagreement here is a
     * change to what a save stores for a key that declares none.
     */
    public function testTheQualifiedItemsAgreeWithGetItemsAboutThePositionZeroDefaultVariantValue(): void
    {
        $this->seedKey(self::PLAIN_KEY_ID);
        $this->seedKey(self::POSITIONED_KEY_ID, $this->variantKey());
        $this->seedKey(self::VARIANT_KEY_ID, $this->variantKey());

        $store = new Classificationstore();
        // a scalar leaf, an array leaf, a value kept above position 0 and a named variant, in two languages
        $store->setLocalizedKeyValue(self::GROUP_ID, self::PLAIN_KEY_ID, 'scalar');
        $store->setLocalizedKeyValue(self::GROUP_ID, self::PLAIN_KEY_ID, ['a', 'b'], 'en');
        $store->setQualifiedKeyValue(self::GROUP_ID, self::POSITIONED_KEY_ID, 'red');
        $store->setQualifiedKeyValue(self::GROUP_ID, self::POSITIONED_KEY_ID, 'blue', null, null, 1);
        $store->setKeyValues(self::GROUP_ID, self::VARIANT_KEY_ID, [
            new MultiValue('1.8 m', null, self::METRIC),
            new MultiValue('plain'),
        ]);

        $qualified = $store->getQualifiedItems();

        foreach ($store->getItems() as $groupId => $keys) {
            foreach ($keys as $keyId => $languages) {
                foreach ($languages as $language => $value) {
                    $this->assertSame(
                        $value,
                        $qualified[$groupId][$keyId][$language]['default'][0] ?? null,
                        'getItems() and getQualifiedItems() disagree about ' . $groupId . '/' . $keyId . '/' . $language
                    );
                }
            }
        }

        // and the qualified reader describes nothing getItems() does not know the key of
        $itemKeyIds = array_keys($store->getItems()[self::GROUP_ID]);
        $qualifiedKeyIds = array_keys($qualified[self::GROUP_ID]);
        sort($itemKeyIds);
        sort($qualifiedKeyIds);

        $this->assertSame([self::GROUP_ID], array_keys($qualified));
        $this->assertSame($itemKeyIds, $qualifiedKeyIds);
    }

    /**
     * @param string[] $variants
     */
    private function variantKey(array $variants = [self::METRIC, self::IMPERIAL]): MultiValueKey
    {
        return $this->multiValueKey($variants);
    }

    /**
     * @param string[] $variants
     */
    private function multiValueKey(array $variants): MultiValueKey
    {
        $inner = new Input();
        $inner->setName('mvInner');

        $definition = new MultiValueKey();
        $definition->setName('mvKey');
        $definition->setVariants(array_map(static fn (string $name): array => ['name' => $name], $variants));
        $definition->setDelegateDatatype($inner->getFieldType());
        $definition->setDelegate($inner);

        return $definition;
    }

    /**
     * The key configuration the model resolves a declaration from, held in the cache the read path
     * asks rather than written to the database - this is what a declaration looks like to the model.
     */
    private function seedKey(int $keyId, ?MultiValueKey $definition = null): void
    {
        $definition ??= $this->plainKey();

        $this->seedKeyConfig($keyId, $definition->getFieldType(), (string) json_encode($definition));
    }

    /**
     * @param string[] $variants
     */
    private function decliningKey(array $variants): MultiValueKey
    {
        return $this->variantKey($variants)->setUnqualifiedValue(false);
    }

    /**
     * The same, seeded from the definition blob itself: a blob that does not carry a member the
     * definition class does is what a key written before that member existed looks like.
     *
     * @param array<string, mixed> $blob
     */
    private function seedKeyFromBlob(int $keyId, array $blob): void
    {
        $this->seedKeyConfig($keyId, 'multiValue', (string) json_encode($blob));
    }

    /**
     * A key of a type other than `multiValue`, which carries no qualifier declaration at all.
     */
    private function seedKeyOfAnotherType(int $keyId): void
    {
        $definition = new Input();
        $definition->setName('mvKey');

        $this->seedKeyConfig($keyId, $definition->getFieldType(), (string) json_encode($definition));
    }

    private function seedKeyConfig(int $keyId, string $type, string $definition): void
    {
        $keyConfig = new KeyConfig();
        $keyConfig->setId($keyId);
        $keyConfig->setName('mvKey' . $keyId);
        $keyConfig->setEnabled(true);
        $keyConfig->setType($type);
        $keyConfig->setDefinition($definition);

        DefinitionCache::put($keyConfig);
        // the resolved definition is memoized by key id, so a re-seeded key has to drop it
        Service::clearDefinitionsCache();
    }

    private function plainKey(): MultiValueKey
    {
        return $this->multiValueKey([]);
    }
}
