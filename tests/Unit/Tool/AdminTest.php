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

namespace Pimcore\Tests\Unit\Tool;

use Pimcore;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tool\Admin;

/**
 * Admin::getLanguages() is called for every admin translation lookup, so it scans the
 * translations directory once per request and answers from the RuntimeCache afterwards.
 */
class AdminTest extends TestCase
{
    private const LANGUAGE = 'yo';

    private string $translationsDir;

    private string $translationFile;

    private bool $createdTranslationsDir = false;

    protected function setUp(): void
    {
        parent::setUp();

        RuntimeCache::clear();

        $this->translationsDir = Pimcore::getContainer()->getParameter('translator.default_path');
        $this->translationFile = $this->translationsDir . '/admin.' . self::LANGUAGE . '.json';

        if (!is_dir($this->translationsDir)) {
            mkdir($this->translationsDir, 0777, true);
            $this->createdTranslationsDir = true;
        }
    }

    protected function tearDown(): void
    {
        if (is_file($this->translationFile)) {
            unlink($this->translationFile);
        }

        if ($this->createdTranslationsDir) {
            rmdir($this->translationsDir);
        }

        RuntimeCache::clear();

        parent::tearDown();
    }

    public function testFindsTheLanguagesOfTheTranslationFiles(): void
    {
        file_put_contents($this->translationFile, '{}');

        $this->assertContains(self::LANGUAGE, Admin::getLanguages());
    }

    public function testScansTheTranslationsDirectoryOncePerRequest(): void
    {
        $languages = Admin::getLanguages();
        $this->assertNotContains(self::LANGUAGE, $languages);

        // a file added later in the same request is not seen: the second call does not scan again
        file_put_contents($this->translationFile, '{}');
        $this->assertSame($languages, Admin::getLanguages());

        // once the RuntimeCache is cleared (next request or messenger message), the directory is scanned again
        RuntimeCache::clear();
        $this->assertContains(self::LANGUAGE, Admin::getLanguages());
    }
}
