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

namespace Pimcore\Tests\Model\DataObject\ClassDefinition;

use Pimcore\Model\DataObject\ClassDefinition\Data\Multiselect;
use Pimcore\Model\DataObject\ClassDefinition\Data\OptionsProviderInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Model\DataObject\ClassDefinition\Layout\Panel;
use Pimcore\Model\DataObject\SelectOptions\Config;
use Pimcore\Model\DataObject\SelectOptions\Data\SelectOption;
use Pimcore\Model\DataObject\Service;
use Pimcore\Tests\Support\Test\ModelTestCase;

/**
 * Select fields using shared select options are enriched from the referenced configuration even
 * when no provider class was persisted (which is the case for field collections and object bricks
 * saved via Studio).
 */
class SelectOptionsProviderEnrichmentTest extends ModelTestCase
{
    private const CONFIG_ID = 'EnrichmentStatus';

    private ?Config $config = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = (new Config())
            ->setId(self::CONFIG_ID)
            ->setSelectOptions(
                new SelectOption('open', 'Open', 'Open'),
                new SelectOption('closed', 'Closed', 'Closed'),
            );
        $this->config->save();
    }

    protected function tearDown(): void
    {
        $this->config?->delete();
        $this->config = null;

        parent::tearDown();
    }

    /**
     * @dataProvider fieldProvider
     */
    public function testFieldWithoutProviderClassIsEnrichedFromSharedSelectOptions(Select|Multiselect $field): void
    {
        $field->setName('status');
        $field->setOptionsProviderType(OptionsProviderInterface::TYPE_SELECT_OPTIONS);
        $field->setOptionsProviderData(self::CONFIG_ID);

        $layout = new Panel();
        $layout->setChildren([$field]);
        Service::enrichLayoutDefinition($layout, null, ['containerType' => 'fieldcollection']);

        $this->assertSame([
            ['value' => 'open', 'key' => 'Open'],
            ['value' => 'closed', 'key' => 'Closed'],
        ], $field->getOptions());
    }

    public static function fieldProvider(): iterable
    {
        yield 'select' => [new Select()];
        yield 'multiselect' => [new Multiselect()];
    }
}
