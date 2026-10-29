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

use Pimcore\Model\DataObject\ClassDefinition\Data\Password;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Tests\Support\Test\TestCase;

class PasswordTest extends TestCase
{
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
}
