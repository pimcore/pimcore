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

namespace Pimcore\Tests\Model\SeoBundle\Redirect;

use InvalidArgumentException;
use Pimcore\Bundle\SeoBundle\Model\Redirect\Listing;
use Pimcore\Tests\Support\Test\TestCase;

final class ListingTest extends TestCase
{
    /**
     * @dataProvider validOrderKeyProvider
     */
    public function testValidOrderKeysAreAccepted(string $key): void
    {
        $listing = new Listing();
        $this->assertTrue($listing->isValidOrderKey($key));
    }

    public static function validOrderKeyProvider(): array
    {
        return array_map(static fn (string $key) => [$key], [
            'id',
            'type',
            'source',
            'sourceSite',
            'target',
            'targetSite',
            'statusCode',
            'priority',
            'regex',
            'passThroughParameters',
            'active',
            'expiry',
            'creationDate',
            'modificationDate',
            'userOwner',
            'userModification',
        ]);
    }

    public function testArbitraryColumnNameIsRejected(): void
    {
        $listing = new Listing();
        $this->assertFalse($listing->isValidOrderKey('some_unknown_column'));
    }

    public function testOrderKeyOutsideTheAllowlistIsRejected(): void
    {
        $listing = new Listing();

        // Not a real column: previously accepted unconditionally since
        // isValidOrderKey() returned true for any input.
        $payload = 'id" ASC,(SELECT SLEEP(5))-- -';

        $this->assertFalse($listing->isValidOrderKey($payload));

        
        $this->expectException(InvalidArgumentException::class);
        $listing->setOrderKey($payload);
    }
}
