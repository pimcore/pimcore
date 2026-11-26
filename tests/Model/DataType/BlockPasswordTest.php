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

namespace Pimcore\Tests\Model\DataType;

use Pimcore\Model\DataObject\ClassDefinition\Data\Block;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\Password;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\DataObject\Data\BlockElement;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tool\Serialize;

/**
 * @group model.datatype.block
 */
class BlockPasswordTest extends ModelTestCase
{
    private function buildBlock(): Block
    {
        $password = new Password();
        $password->setName('secret');

        $block = new Block();
        $block->setName('blockfield');
        $block->setChildren([$password]);

        return $block;
    }

    private function extractStored(string $serialized, string $element): mixed
    {
        $result = Serialize::unserialize($serialized, true);

        return $result[0][$element]['data'];
    }

    public function testPasswordInBlockIsHashedOnSave(): void
    {
        $block = $this->buildBlock();
        $element = new BlockElement('secret', 'password', 'Plain-Value-123');

        $serialized = $block->getDataForResource([['secret' => $element]], null, [
            'owner' => $this->createStub(\Pimcore\Model\DataObject\Concrete::class),
            'fieldname' => 'blockfield',
        ]);

        $stored = $this->extractStored($serialized, 'secret');
        $this->assertIsString($stored);
        $this->assertNotSame('Plain-Value-123', $stored);
        $this->assertNotNull(password_get_info($stored)['algo']);
        $this->assertTrue(password_verify('Plain-Value-123', $stored));

        // in-memory data holds the hash as well
        $this->assertSame($stored, $element->getData());
    }

    public function testHashedPasswordInBlockIsNotRehashed(): void
    {
        $block = $this->buildBlock();
        $element = new BlockElement('secret', 'password', 'Plain-Value-123');
        $params = [
            'owner' => $this->createStub(\Pimcore\Model\DataObject\Concrete::class),
            'fieldname' => 'blockfield',
        ];

        $first = $this->extractStored($block->getDataForResource([['secret' => $element]], null, $params), 'secret');
        $second = $this->extractStored($block->getDataForResource([['secret' => $element]], null, $params), 'secret');

        $this->assertSame($first, $second);
    }

    public function testLocalizedPasswordInBlockIsHashedForEveryLanguage(): void
    {
        $password = new Password();
        $password->setName('secret');
        $localizedfields = new Localizedfields();
        $localizedfields->setName('localizedfields');
        $localizedfields->setChildren([$password]);

        $block = new Block();
        $block->setName('blockfield');
        $block->setChildren([$localizedfields]);

        $localizedfield = new Localizedfield([
            'en' => ['secret' => 'Plain-En-123'],
            'de' => ['secret' => 'Plain-De-456'],
        ]);
        $localizedfield->setContext(['containerType' => 'block', 'containerKey' => 'blockfield']);
        $element = new BlockElement('localizedfields', 'localizedfields', $localizedfield);

        $serialized = $block->getDataForResource([['localizedfields' => $element]], null, [
            'owner' => $this->createStub(\Pimcore\Model\DataObject\Concrete::class),
            'fieldname' => 'blockfield',
        ]);

        $stored = $this->extractStored($serialized, 'localizedfields');
        $this->assertIsArray($stored);

        foreach (['en' => 'Plain-En-123', 'de' => 'Plain-De-456'] as $language => $plain) {
            $hash = $stored[$language]['secret'];
            $this->assertIsString($hash);
            $this->assertNotSame($plain, $hash);
            $this->assertNotNull(password_get_info($hash)['algo']);
            $this->assertTrue(password_verify($plain, $hash));

            // in-memory localized value holds the hash too
            $this->assertSame($hash, $localizedfield->getItems()[$language]['secret']);
        }
    }

    public function testEmptyPasswordInBlockStaysEmpty(): void
    {
        $block = $this->buildBlock();
        $element = new BlockElement('secret', 'password', null);

        $serialized = $block->getDataForResource([['secret' => $element]], null, [
            'owner' => $this->createStub(\Pimcore\Model\DataObject\Concrete::class),
            'fieldname' => 'blockfield',
        ]);

        $this->assertEmpty($this->extractStored($serialized, 'secret'));
    }
}
