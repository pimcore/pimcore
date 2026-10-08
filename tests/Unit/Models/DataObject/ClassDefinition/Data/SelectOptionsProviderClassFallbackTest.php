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

namespace Pimcore\Tests\Unit\Model\DataObject\ClassDefinition\Data;

use Pimcore\Bundle\CoreBundle\OptionsProvider\SelectOptionsOptionsProvider;
use Pimcore\Model\DataObject\ClassDefinition\Data\Multiselect;
use Pimcore\Model\DataObject\ClassDefinition\Data\OptionsProviderInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * A field whose options come from shared select options only needs the provider type and the
 * configuration id to be persisted; the provider class is implied by the type.
 *
 * @group unit.model.datatype.select
 */
class SelectOptionsProviderClassFallbackTest extends TestCase
{
    /**
     * @dataProvider fieldProvider
     */
    public function testSelectOptionsTypeImpliesTheSharedProvider(Select|Multiselect $field): void
    {
        $field->setOptionsProviderType(OptionsProviderInterface::TYPE_SELECT_OPTIONS);
        $field->setOptionsProviderData('Status');

        $this->assertSame(SelectOptionsOptionsProvider::class, $field->getOptionsProviderClass());
        $this->assertFalse($field->useConfiguredOptions());
    }

    /**
     * @dataProvider fieldProvider
     */
    public function testExplicitProviderClassIsKept(Select|Multiselect $field): void
    {
        $field->setOptionsProviderType(OptionsProviderInterface::TYPE_SELECT_OPTIONS);
        $field->setOptionsProviderClass('@app.custom_options_provider');

        $this->assertSame('@app.custom_options_provider', $field->getOptionsProviderClass());
    }

    /**
     * @dataProvider fieldProvider
     */
    public function testOtherProviderTypesAreNotAffected(Select|Multiselect $field): void
    {
        $field->setOptionsProviderType(OptionsProviderInterface::TYPE_CONFIGURE);
        $this->assertNull($field->getOptionsProviderClass());
        $this->assertTrue($field->useConfiguredOptions());

        $field->setOptionsProviderType(OptionsProviderInterface::TYPE_CLASS);
        $this->assertNull($field->getOptionsProviderClass());

        $field->setOptionsProviderType(null);
        $this->assertNull($field->getOptionsProviderClass());
        $this->assertTrue($field->useConfiguredOptions());
    }

    public static function fieldProvider(): iterable
    {
        yield 'select' => [new Select()];
        yield 'multiselect' => [new Multiselect()];
    }
}
