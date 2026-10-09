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

namespace Pimcore\Tests\Unit\Models\Asset;

use PHPUnit\Framework\TestCase;
use Pimcore\Model\Asset;
use ReflectionMethod;

/**
 * Covers Asset::getMetadataByName(), which used to stop at the first entry matching the
 * name and settle for a language-less entry even when an exact-language entry existed
 * later in the array (GitHub issue #18944). getMetadata() dispatches a PRE_GET_METADATA
 * event that needs a booted container, so the tests call the protected method directly
 * via reflection instead of going through that public wrapper.
 */
class GetMetadataByNameTest extends TestCase
{
    private function callGetMetadataByName(Asset $asset, string $name, ?string $language, bool $strictMatchLanguage = false): mixed
    {
        $method = new ReflectionMethod(Asset::class, 'getMetadataByName');

        return $method->invoke($asset, $name, $language, $strictMatchLanguage, true);
    }

    public function testExactLanguageMatchWinsWhenLanguagelessEntryComesFirst(): void
    {
        $asset = new Asset();
        $asset->setMetadataRaw([
            ['name' => 'alt', 'type' => 'input', 'data' => 'test', 'language' => ''],
            ['name' => 'alt', 'type' => 'input', 'data' => 'test de', 'language' => 'de'],
            ['name' => 'alt', 'type' => 'input', 'data' => 'test en', 'language' => 'en'],
        ]);

        $this->assertSame('test de', $this->callGetMetadataByName($asset, 'alt', 'de')['data']);
        $this->assertSame('test en', $this->callGetMetadataByName($asset, 'alt', 'en')['data']);
    }

    public function testLanguagelessEntryIsUsedAsFallbackWhenNoExactMatchExists(): void
    {
        $asset = new Asset();
        $asset->setMetadataRaw([
            ['name' => 'alt', 'type' => 'input', 'data' => 'test', 'language' => ''],
            ['name' => 'alt', 'type' => 'input', 'data' => 'test en', 'language' => 'en'],
        ]);

        $this->assertSame('test', $this->callGetMetadataByName($asset, 'alt', 'fr')['data']);
    }

    public function testStrictMatchLanguageIgnoresLanguagelessEntry(): void
    {
        $asset = new Asset();
        $asset->setMetadataRaw([
            ['name' => 'alt', 'type' => 'input', 'data' => 'test', 'language' => ''],
        ]);

        $this->assertNull($this->callGetMetadataByName($asset, 'alt', 'de', true));
    }
}
