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

use Exception;
use Pimcore\Model\DataObject\ClassDefinition\Data\Password;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\BlockElement;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData;
use Pimcore\Tests\Support\Test\TestCase;

class PasswordTest extends TestCase
{
    public const HASH_FOR_ITEM = '$2y$12$itemitemitemitemitemitOuxFoo1pNFmo0Q9bZbc6PCkqzcLzXbJe';

    public const HASH = '$2y$12$hvBjG/fHXnkrQXIuf2Rf4.Ev8Ln1roR3RB/zL9QOHGjbnFptgkSfi';

    private function buildField(): Password
    {
        $field = new Password();
        $field->setName('secret');

        return $field;
    }

    public function testGetDataForEditmodeReturnsNullWhenNoHashIsStored(): void
    {
        $this->assertNull($this->buildField()->getDataForEditmode(null));
    }

    public function testGetDataForEditmodeMasksStoredHash(): void
    {
        $hash = '$2y$12$hvBjG/fHXnkrQXIuf2Rf4.Ev8Ln1roR3RB/zL9QOHGjbnFptgkSfi';

        $result = $this->buildField()->getDataForEditmode($hash);

        $this->assertSame('******', $result);
        $this->assertNotSame($hash, $result);
    }

    public function testGetDataFromEditmodeKeepsStoredHashWhenMaskIsSubmittedUnchanged(): void
    {
        $hash = '$2y$12$hvBjG/fHXnkrQXIuf2Rf4.Ev8Ln1roR3RB/zL9QOHGjbnFptgkSfi';

        $object = new class extends Concrete {
            private ?string $secret = null;

            public function getSecret(): ?string
            {
                return $this->secret;
            }

            public function setSecret(?string $secret): void
            {
                $this->secret = $secret;
            }
        };
        $object->setSecret($hash);

        $result = $this->buildField()->getDataFromEditmode('******', $object);

        $this->assertSame($hash, $result);
    }

    public function testGetDataFromEditmodeAcceptsNewPlaintextValue(): void
    {
        $result = $this->buildField()->getDataFromEditmode('new-plaintext-password');

        $this->assertSame('new-plaintext-password', $result);
    }

    public function testGetDataFromEditmodeReturnsNullForEmptyString(): void
    {
        $this->assertNull($this->buildField()->getDataFromEditmode(''));
    }

    public function testGetDataFromEditmodeWithMaskAndNoObjectDoesNotClearOrPersistTheMask(): void
    {
        $this->expectException(Exception::class);

        $this->buildField()->getDataFromEditmode('******', null);
    }

    public function testGetDataFromEditmodeWithMaskFailsSafeWhenOwnerHasNoGetter(): void
    {
        $object = new class extends Concrete {
        };

        $this->expectException(Exception::class);

        $this->buildField()->getDataFromEditmode('******', $object);
    }

    public function testGetDataFromEditmodeKeepsStoredHashInsideFieldcollection(): void
    {
        $item = new class extends AbstractData {
            public function getType(): string
            {
                return 'item';
            }

            public function getSecret(): ?string
            {
                return PasswordTest::HASH_FOR_ITEM;
            }
        };
        $object = new class($item) extends Concrete {
            public function __construct(private AbstractData $item)
            {
            }

            public function getCollection(): Fieldcollection
            {
                return new Fieldcollection([$this->item], 'collection');
            }
        };

        $result = $this->buildField()->getDataFromEditmode('******', $object, [
            'context' => ['containerType' => 'fieldcollection', 'fieldname' => 'collection', 'index' => 0, 'oIndex' => 0],
        ]);

        $this->assertSame(self::HASH_FOR_ITEM, $result);
    }

    public function testGetDataFromEditmodeWithMaskInsideNewFieldcollectionItemStoresNothing(): void
    {
        $object = new class extends Concrete {
        };

        $result = $this->buildField()->getDataFromEditmode('******', $object, [
            'context' => ['containerType' => 'fieldcollection', 'fieldname' => 'collection', 'index' => 0, 'oIndex' => null],
        ]);

        $this->assertNull($result);
    }

    public function testGetDataFromEditmodeKeepsStoredHashInsideObjectbrick(): void
    {
        $brick = new class() {
            public function getSecret(): string
            {
                return PasswordTest::HASH_FOR_ITEM;
            }
        };
        $container = new class($brick) {
            public function __construct(private object $brick)
            {
            }

            public function getMyBrick(): object
            {
                return $this->brick;
            }
        };
        $object = new class($container) extends Concrete {
            public function __construct(private object $container)
            {
            }

            public function getBricks(): object
            {
                return $this->container;
            }
        };

        $result = $this->buildField()->getDataFromEditmode('******', $object, [
            'context' => ['containerType' => 'objectbrick', 'fieldname' => 'bricks', 'containerKey' => 'myBrick'],
        ]);

        $this->assertSame(self::HASH_FOR_ITEM, $result);
    }

    public function testGetDataFromEditmodeKeepsStoredHashInsideBlock(): void
    {
        $object = new class extends Concrete {
            public function getBlocks(?string $language = null): array
            {
                return [
                    ['secret' => new BlockElement('secret', 'password', 'first')],
                    ['secret' => new BlockElement('secret', 'password', PasswordTest::HASH_FOR_ITEM)],
                ];
            }
        };

        $result = $this->buildField()->getDataFromEditmode('******', $object, [
            'context' => ['containerType' => 'block', 'fieldname' => 'blocks', 'index' => 0, 'oIndex' => 1],
        ]);

        $this->assertSame(self::HASH_FOR_ITEM, $result);
    }

    public function testGetDataFromEditmodeWithMaskInsideUnresolvableBlockFailsSafe(): void
    {
        $object = new class extends Concrete {
        };

        $this->expectException(Exception::class);

        $this->buildField()->getDataFromEditmode('******', $object, [
            'context' => ['containerType' => 'block', 'fieldname' => 'blocks', 'index' => 0, 'oIndex' => 0],
        ]);
    }

    public function testGetDataFromEditmodeKeepsStoredHashPerLanguageOfLocalizedField(): void
    {
        $object = new class extends Concrete {
            public function getSecret(?string $language = null): ?string
            {
                return $language === 'de' ? PasswordTest::HASH_FOR_ITEM : PasswordTest::HASH;
            }
        };
        $field = $this->buildField();

        $this->assertSame(self::HASH_FOR_ITEM, $field->getDataFromEditmode('******', $object, ['language' => 'de']));
        $this->assertSame(self::HASH, $field->getDataFromEditmode('******', $object, ['language' => 'en']));
    }

    public function testGetDataFromEditmodeKeepsInjectedStoredValue(): void
    {
        // e.g. classification store, which resolves the stored value itself
        $field = $this->buildField();

        $this->assertSame(self::HASH, $field->getDataFromEditmode('******', null, ['injectedData' => self::HASH]));
        $this->assertNull($field->getDataFromEditmode('******', null, ['injectedData' => null]));
    }

    public function testGetDataFromEditmodeWithMaskInsideUnsupportedContainerFailsSafe(): void
    {
        $object = new class extends Concrete {
            public function getSecret(): string
            {
                return PasswordTest::HASH_FOR_ITEM;
            }
        };

        $this->expectException(Exception::class);

        $this->buildField()->getDataFromEditmode('******', $object, [
            'context' => ['containerType' => 'somethingElse', 'fieldname' => 'x'],
        ]);
    }

    public function testGetForCsvExportDoesNotExposeTheStoredHash(): void
    {
        $object = new class extends Concrete {
            private ?string $secret = null;

            public function getSecret(): ?string
            {
                return $this->secret;
            }

            public function setSecret(?string $secret): void
            {
                $this->secret = $secret;
            }
        };
        $field = $this->buildField();

        $this->assertSame('', $field->getForCsvExport($object));

        $object->setSecret(self::HASH);
        $result = $field->getForCsvExport($object);

        $this->assertSame('******', $result);
        $this->assertStringNotContainsString(self::HASH, $result);
    }
}
