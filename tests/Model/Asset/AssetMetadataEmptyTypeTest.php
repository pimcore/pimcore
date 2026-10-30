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

namespace Pimcore\Tests\Model\Asset;

use Monolog\Handler\TestHandler;
use Monolog\Logger as MonologLogger;
use Pimcore;
use Pimcore\Model\Asset;
use Pimcore\Tests\Support\Test\ModelTestCase;

/**
 * Metadata rows with an empty type (the assets_metadata.type ENUM stores out-of-enum values as '')
 * must not reach the metadata data loader.
 *
 * @group model.asset.asset
 */
class AssetMetadataEmptyTypeTest extends ModelTestCase
{
    public function testEmptyTypeReturnsRawDataWithoutLoggingAnError(): void
    {
        $logger = Pimcore::getContainer()->get('monolog.logger.pimcore');
        $this->assertInstanceOf(MonologLogger::class, $logger);

        $handler = new TestHandler();
        $logger->pushHandler($handler);

        try {
            $asset = new Asset\Image();
            $asset->setMetadataRaw([
                ['name' => 'legacy', 'type' => '', 'data' => 'some value', 'language' => ''],
            ]);

            $this->assertSame('some value', $asset->getMetadata('legacy'));

            $entries = $asset->getMetadata(null, null, false, false);
            $this->assertSame('some value', $entries[0]['data']);
        } finally {
            $logger->popHandler();
        }

        $this->assertFalse($handler->hasErrorRecords(), 'No error must be logged for an empty metadata type');
    }
}
