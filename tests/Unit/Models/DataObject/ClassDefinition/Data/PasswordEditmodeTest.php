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
use Pimcore\Model\DataObject\ClassDefinition\Data\Block;
use Pimcore\Model\DataObject\ClassDefinition\Data\Password;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\BlockElement;
use Pimcore\Model\DataObject\Data\EncryptedField;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Tests\Support\Test\TestCase;

class PasswordEditmodeTest extends TestCase
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
                return PasswordEditmodeTest::HASH_FOR_ITEM;
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
                return PasswordEditmodeTest::HASH_FOR_ITEM;
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
                    ['secret' => new BlockElement('secret', 'password', PasswordEditmodeTest::HASH_FOR_ITEM)],
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
                return $language === 'de' ? PasswordEditmodeTest::HASH_FOR_ITEM : PasswordEditmodeTest::HASH;
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
                return PasswordEditmodeTest::HASH_FOR_ITEM;
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

    public function testGetDataFromEditmodeUsesSubmittedLanguageInsideFieldcollection(): void
    {
        $item = new class extends AbstractData {
            public function getSecret(?string $language = null): ?string
            {
                // the active locale is English, the submitted language is German
                return $language === 'de' ? PasswordEditmodeTest::HASH_FOR_ITEM : PasswordEditmodeTest::HASH;
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
            'language' => 'de',
            'context' => ['containerType' => 'fieldcollection', 'fieldname' => 'collection', 'index' => 0, 'oIndex' => 0],
        ]);

        $this->assertSame(self::HASH_FOR_ITEM, $result);
    }

    public function testGetDataFromEditmodeUsesSubmittedLanguageInsideObjectbrick(): void
    {
        $brick = new class() {
            public function getSecret(?string $language = null): string
            {
                return $language === 'de' ? PasswordEditmodeTest::HASH_FOR_ITEM : PasswordEditmodeTest::HASH;
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
            'language' => 'de',
            'context' => ['containerType' => 'objectbrick', 'fieldname' => 'bricks', 'containerKey' => 'myBrick'],
        ]);

        $this->assertSame(self::HASH_FOR_ITEM, $result);
    }

    public function testGetDataFromEditmodeKeepsStoredHashOfLocalizedFieldInsideBlock(): void
    {
        $localized = (new Localizedfield())->setItems([
            'de' => ['secret' => self::HASH_FOR_ITEM],
            'en' => ['secret' => self::HASH],
        ]);
        $object = new class($localized) extends Concrete {
            public function __construct(private readonly Localizedfield $localized)
            {
            }

            public function getBlocks(?string $language = null): array
            {
                return [['localizedfields' => new BlockElement('localizedfields', 'localizedfields', $this->localized)]];
            }
        };
        $field = $this->buildField();
        $context = ['containerType' => 'block', 'fieldname' => 'blocks', 'index' => 0, 'oIndex' => 0];

        $this->assertSame(self::HASH_FOR_ITEM, $field->getDataFromEditmode('******', $object, ['language' => 'de', 'context' => $context]));
        $this->assertSame(self::HASH, $field->getDataFromEditmode('******', $object, ['language' => 'en', 'context' => $context]));
    }

    public function testGetDataFromEditmodeWithMaskFailsSafeWhenBlockElementIsMissing(): void
    {
        $object = new class extends Concrete {
            public function getBlocks(?string $language = null): array
            {
                return [['other' => new BlockElement('other', 'input', 'x')]];
            }
        };

        $this->expectException(Exception::class);

        $this->buildField()->getDataFromEditmode('******', $object, [
            'language' => 'de',
            'context' => ['containerType' => 'block', 'fieldname' => 'blocks', 'index' => 0, 'oIndex' => 0],
        ]);
    }

    public function testBlockForwardsTheSubmittedLanguageToItsPasswordChild(): void
    {
        // a block inside a localized field: the block receives the language and has to pass it on
        $object = new class extends Concrete {
            public function getBlocks(?string $language = null): array
            {
                $hash = $language === 'de' ? PasswordEditmodeTest::HASH_FOR_ITEM : PasswordEditmodeTest::HASH;

                return [['secret' => new BlockElement('secret', 'password', $hash)]];
            }

            public function getClassId(): string
            {
                return 'x';
            }
        };
        $block = new Block();
        $block->setName('blocks');
        $block->setChildren([$this->buildField()]);

        $result = $block->getDataFromEditmode([['oIndex' => 0, 'data' => ['secret' => '******']]], $object, ['language' => 'de']);

        $this->assertSame(self::HASH_FOR_ITEM, $result[0]['secret']->getData());
    }

    public function testGetDataFromEditmodeKeepsStoredHashOfEncryptedPassword(): void
    {
        $field = $this->buildField();
        $wrapped = new EncryptedField($field, self::HASH);

        $topLevel = new class($wrapped) extends Concrete {
            public function __construct(private readonly EncryptedField $wrapped)
            {
            }

            public function getSecret(): EncryptedField
            {
                return $this->wrapped;
            }
        };
        $inBlock = new class($wrapped) extends Concrete {
            public function __construct(private readonly EncryptedField $wrapped)
            {
            }

            public function getBlocks(?string $language = null): array
            {
                return [['secret' => new BlockElement('secret', 'encryptedField', $this->wrapped)]];
            }
        };
        $inLocalizedBlock = new class($wrapped) extends Concrete {
            public function __construct(private readonly EncryptedField $wrapped)
            {
            }

            public function getBlocks(?string $language = null): array
            {
                $localized = (new Localizedfield())->setItems(['de' => ['secret' => $this->wrapped]]);

                return [['localizedfields' => new BlockElement('localizedfields', 'localizedfields', $localized)]];
            }
        };
        $context = ['containerType' => 'block', 'fieldname' => 'blocks', 'index' => 0, 'oIndex' => 0];

        $this->assertSame(self::HASH, $field->getDataFromEditmode('******', $topLevel));
        $this->assertSame(self::HASH, $field->getDataFromEditmode('******', $inBlock, ['context' => $context]));
        $this->assertSame(
            self::HASH,
            $field->getDataFromEditmode('******', $inLocalizedBlock, ['language' => 'de', 'context' => $context])
        );
    }
}
