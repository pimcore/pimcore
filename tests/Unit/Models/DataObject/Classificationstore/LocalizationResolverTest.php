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

namespace Pimcore\Tests\Unit\Model\DataObject\Classificationstore;

use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore as ClassificationstoreField;
use Pimcore\Model\DataObject\Classificationstore\DefinitionCache;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\LocalizationResolver;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use ReflectionClass;

/**
 * The effective localization of a key: its own flag when it carries one, the flag of the class
 * definition field when it does not.
 */
class LocalizationResolverTest extends TestCase
{
    private const KEY_ID = 4711;

    /**
     * An id no key config can answer, so that the resolver meets the deleted-key case.
     */
    private const VANISHED_KEY_ID = 2147483600;

    protected function tearDown(): void
    {
        DefinitionCache::clear(null);

        parent::tearDown();
    }

    /**
     * @return array<string, array{0: bool|null, 1: bool, 2: bool}>
     */
    public static function effectiveLocalizationProvider(): array
    {
        return [
            'no flag in a non-localized field' => [null, false, false],
            'no flag in a localized field' => [null, true, true],
            'flagged true in a non-localized field' => [true, false, true],
            'flagged true in a localized field' => [true, true, true],
            'flagged false in a non-localized field' => [false, false, false],
            'flagged false in a localized field' => [false, true, false],
        ];
    }

    /**
     * @dataProvider effectiveLocalizationProvider
     */
    public function testTheKeyFlagDecidesAndNullInheritsTheField(
        ?bool $keyFlag,
        bool $fieldFlag,
        bool $expected
    ): void {
        $keyConfig = new KeyConfig();
        $keyConfig->setId(self::KEY_ID);
        $keyConfig->setLocalized($keyFlag);
        DefinitionCache::put($keyConfig);

        $this->assertSame(
            $expected,
            $this->resolver()->isKeyLocalized(self::KEY_ID, $this->field($fieldFlag))
        );
    }

    /**
     * A key loaded from a row whose column is NULL never reaches the setter, so the property
     * default is what has to inherit the field.
     */
    public function testAKeyThatWasNeverGivenAFlagInheritsTheField(): void
    {
        $keyConfig = new KeyConfig();
        $keyConfig->setId(self::KEY_ID);
        DefinitionCache::put($keyConfig);

        $this->assertTrue($this->resolver()->isKeyLocalized(self::KEY_ID, $this->field(true)));
        $this->assertFalse($this->resolver()->isKeyLocalized(self::KEY_ID, $this->field(false)));
    }

    public function testAKeyIdThatResolvesToNoConfigurationFallsBackToTheField(): void
    {
        // the only case here that looks the key up: an id the cache does not hold reaches the DAO
        // through DefinitionCache::get(), so this one case needs a database
        TestHelper::checkDbSupport();

        $this->assertTrue($this->resolver()->isKeyLocalized(self::VANISHED_KEY_ID, $this->field(true)));
        $this->assertFalse($this->resolver()->isKeyLocalized(self::VANISHED_KEY_ID, $this->field(false)));
    }

    /**
     * The key flag alone answers this, so the resolver's own connection is never used - the cases
     * above hand the key config to the cache and the one case below reads it through the cache.
     */
    private function resolver(): LocalizationResolver
    {
        return (new ReflectionClass(LocalizationResolver::class))->newInstanceWithoutConstructor();
    }

    private function field(bool $localized): ClassificationstoreField
    {
        $field = new ClassificationstoreField();
        $field->setLocalized($localized);

        return $field;
    }
}
