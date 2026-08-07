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

namespace Pimcore\Tests\Unit\Model\Workflow\EventSubscriber;

use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Workflow\EventSubscriber\NotesSubscriber;
use ReflectionMethod;
use Symfony\Contracts\Translation\TranslatorInterface;

class NotesSubscriberTest extends TestCase
{
    /**
     * Regression: getAdditionalDataForField() compared checkbox values with a strict
     * `=== 'true'` string check. Callers that hydrate additional-field values as native
     * booleans (rather than the legacy form-encoded strings) send a real `true`, which never
     * matched the string comparison, so a checked "required" checkbox was always read back
     * as false and the transition could never be submitted.
     */
    public function testCheckboxFieldAcceptsNativeBooleanTrue(): void
    {
        $result = $this->getAdditionalDataForCheckbox(true);

        $this->assertTrue($result);
    }

    public function testCheckboxFieldAcceptsNativeBooleanFalse(): void
    {
        $result = $this->getAdditionalDataForCheckbox(false);

        $this->assertFalse($result);
    }

    public function testCheckboxFieldStillAcceptsLegacyStringTrue(): void
    {
        $result = $this->getAdditionalDataForCheckbox('true');

        $this->assertTrue($result);
    }

    public function testCheckboxFieldStillAcceptsLegacyStringFalse(): void
    {
        $result = $this->getAdditionalDataForCheckbox('false');

        $this->assertFalse($result);
    }

    private function getAdditionalDataForCheckbox(mixed $rawValue): mixed
    {
        $subscriber = new NotesSubscriber($this->createStub(TranslatorInterface::class));
        $subscriber->setAdditionalData([
            'additional' => [
                'marketingInvolved' => $rawValue,
            ],
        ]);

        $method = new ReflectionMethod($subscriber, 'getAdditionalDataForField');
        $method->setAccessible(true);

        return $method->invoke($subscriber, [
            'name' => 'marketingInvolved',
            'fieldType' => 'checkbox',
            'title' => 'Please confirm that Marketing was sufficiently involved',
            'required' => true,
        ]);
    }
}
