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

namespace Pimcore\Tests\Unit\InstallBundle\EnvVarDefinition\Validation;

use Pimcore\Bundle\InstallBundle\EnvVarDefinition\Validation\FormatValidator;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * @internal
 */
final class FormatValidatorTest extends TestCase
{
    private FormatValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new FormatValidator();
    }

    public function testRequireNonEmptyPassesForNonEmptyValue(): void
    {
        $this->validator->requireNonEmpty('hello', 'Field');

        $this->assertFalse($this->validator->hasErrors());
        $this->assertSame([], $this->validator->getErrors());
    }

    public function testRequireNonEmptyFailsForEmptyString(): void
    {
        $this->validator->requireNonEmpty('', 'Username');

        $this->assertTrue($this->validator->hasErrors());
        $errors = $this->validator->getErrors();
        $this->assertCount(1, $errors);
        $this->assertSame('Username is required and cannot be empty.', $errors[0]);
    }

    public function testRequireValidUrlPassesForValidUrl(): void
    {
        $this->validator->requireValidUrl('https://example.com', 'Server');

        $this->assertFalse($this->validator->hasErrors());
        $this->assertSame([], $this->validator->getErrors());
    }

    public function testRequireValidUrlPassesForEmptyValue(): void
    {
        $this->validator->requireValidUrl('', 'Server');

        $this->assertFalse($this->validator->hasErrors());
        $this->assertSame([], $this->validator->getErrors());
    }

    public function testRequireValidUrlFailsForMalformedUrl(): void
    {
        $this->validator->requireValidUrl('http:///missing-host', 'Server');

        $this->assertTrue($this->validator->hasErrors());
        $errors = $this->validator->getErrors();
        $this->assertCount(1, $errors);
        $this->assertSame('Invalid Server URL: "http:///missing-host".', $errors[0]);
    }

    public function testRequireValidUrlFailsForUrlWithoutHost(): void
    {
        $this->validator->requireValidUrl('/just/a/path', 'API');

        $this->assertTrue($this->validator->hasErrors());
        $errors = $this->validator->getErrors();
        $this->assertCount(1, $errors);
        $this->assertSame('Invalid API URL: "/just/a/path".', $errors[0]);
    }

    public function testRequireUrlWithSchemePassesForValidScheme(): void
    {
        $this->validator->requireUrlWithScheme(
            'amqp://user:pass@rabbitmq:5672/%2f',
            'Transport',
            ['amqp', 'amqps'],
        );

        $this->assertFalse($this->validator->hasErrors());
        $this->assertSame([], $this->validator->getErrors());
    }

    public function testRequireUrlWithSchemePassesForEmptyValue(): void
    {
        $this->validator->requireUrlWithScheme('', 'Transport', ['amqp', 'amqps']);

        $this->assertFalse($this->validator->hasErrors());
        $this->assertSame([], $this->validator->getErrors());
    }

    public function testRequireUrlWithSchemeFailsForWrongScheme(): void
    {
        $this->validator->requireUrlWithScheme(
            'http://rabbitmq:5672/%2f',
            'Transport',
            ['amqp', 'amqps'],
        );

        $this->assertTrue($this->validator->hasErrors());
        $errors = $this->validator->getErrors();
        $this->assertCount(1, $errors);
        $this->assertSame(
            'Transport URL must use amqp:// or amqps:// scheme, got "http://".',
            $errors[0],
        );
    }

    public function testRequireUrlWithSchemeFailsForMalformedUrl(): void
    {
        $this->validator->requireUrlWithScheme(
            '/not-a-url',
            'Transport',
            ['amqp', 'amqps'],
        );

        $this->assertTrue($this->validator->hasErrors());
        $errors = $this->validator->getErrors();
        $this->assertCount(1, $errors);
        $this->assertSame('Invalid Transport URL: "/not-a-url".', $errors[0]);
    }

    public function testRequirePortInRangePassesForValidPort(): void
    {
        $this->validator->requirePortInRange(8080, 'Port');

        $this->assertFalse($this->validator->hasErrors());
        $this->assertSame([], $this->validator->getErrors());
    }

    public function testRequirePortInRangeFailsForZero(): void
    {
        $this->validator->requirePortInRange(0, 'Port');

        $this->assertTrue($this->validator->hasErrors());
        $errors = $this->validator->getErrors();
        $this->assertCount(1, $errors);
        $this->assertSame('Port must be between 1 and 65535, got 0.', $errors[0]);
    }

    public function testRequirePortInRangeFailsForNegative(): void
    {
        $this->validator->requirePortInRange(-1, 'Port');

        $this->assertTrue($this->validator->hasErrors());
        $errors = $this->validator->getErrors();
        $this->assertCount(1, $errors);
        $this->assertSame('Port must be between 1 and 65535, got -1.', $errors[0]);
    }

    public function testRequirePortInRangeFailsForTooHigh(): void
    {
        $this->validator->requirePortInRange(65536, 'Port');

        $this->assertTrue($this->validator->hasErrors());
        $errors = $this->validator->getErrors();
        $this->assertCount(1, $errors);
        $this->assertSame('Port must be between 1 and 65535, got 65536.', $errors[0]);
    }

    public function testRequirePortInRangeBoundaryMin(): void
    {
        $this->validator->requirePortInRange(1, 'Port');

        $this->assertFalse($this->validator->hasErrors());
        $this->assertSame([], $this->validator->getErrors());
    }

    public function testRequirePortInRangeBoundaryMax(): void
    {
        $this->validator->requirePortInRange(65535, 'Port');

        $this->assertFalse($this->validator->hasErrors());
        $this->assertSame([], $this->validator->getErrors());
    }

    public function testRequireMinLengthPassesForLongEnoughValue(): void
    {
        $this->validator->requireMinLength('longpassword', 'Password', 8);

        $this->assertFalse($this->validator->hasErrors());
        $this->assertSame([], $this->validator->getErrors());
    }

    public function testRequireMinLengthPassesForEmptyValue(): void
    {
        $this->validator->requireMinLength('', 'Password', 8);

        $this->assertFalse($this->validator->hasErrors());
        $this->assertSame([], $this->validator->getErrors());
    }

    public function testRequireMinLengthFailsForTooShortValue(): void
    {
        $this->validator->requireMinLength('abc', 'Password', 8);

        $this->assertTrue($this->validator->hasErrors());
        $errors = $this->validator->getErrors();
        $this->assertCount(1, $errors);
        $this->assertSame('Password must be at least 8 characters.', $errors[0]);
    }

    public function testHasErrorsReturnsFalseWhenNoErrors(): void
    {
        $this->assertFalse($this->validator->hasErrors());
    }

    public function testHasErrorsReturnsTrueWhenErrorsExist(): void
    {
        $this->validator->requireNonEmpty('', 'Field');

        $this->assertTrue($this->validator->hasErrors());
    }

    public function testGetErrorsReturnsErrorsWithoutClearing(): void
    {
        $this->validator->requireNonEmpty('', 'Field');

        $this->assertTrue($this->validator->hasErrors());

        $errors = $this->validator->getErrors();
        $this->assertCount(1, $errors);
        $this->assertSame('Field is required and cannot be empty.', $errors[0]);

        // getErrors() is read-only — errors are still present
        $this->assertTrue($this->validator->hasErrors());
        $this->assertSame($errors, $this->validator->getErrors());
    }

    public function testFluentChaining(): void
    {
        $errors = $this->validator
            ->requireNonEmpty('', 'Host')
            ->requireValidUrl('not-a-url', 'Endpoint')
            ->requirePortInRange(0, 'Port')
            ->requireMinLength('ab', 'Secret', 5)
            ->getErrors();

        $this->assertCount(4, $errors);
        $this->assertSame('Host is required and cannot be empty.', $errors[0]);
        $this->assertSame('Invalid Endpoint URL: "not-a-url".', $errors[1]);
        $this->assertSame('Port must be between 1 and 65535, got 0.', $errors[2]);
        $this->assertSame('Secret must be at least 5 characters.', $errors[3]);
    }

    public function testMultipleErrorsCollected(): void
    {
        $this->validator->requireNonEmpty('', 'Username');
        $this->validator->requireNonEmpty('', 'Password');
        $this->validator->requirePortInRange(99999, 'DB Port');

        $errors = $this->validator->getErrors();

        $this->assertCount(3, $errors);
        $this->assertSame('Username is required and cannot be empty.', $errors[0]);
        $this->assertSame('Password is required and cannot be empty.', $errors[1]);
        $this->assertSame('DB Port must be between 1 and 65535, got 99999.', $errors[2]);
    }
}
