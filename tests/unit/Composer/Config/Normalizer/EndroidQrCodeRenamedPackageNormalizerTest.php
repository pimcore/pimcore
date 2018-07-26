<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Tests\Unit\Composer\Config\Normalizer;

use Pimcore\Composer\Config\Normalizer\EndroidQrCodeRenamedPackageNormalizer;
use Pimcore\Tests\Test\TestCase;

/**
 * @covers EndroidQrCodeRenamedPackageNormalizer
 */
class EndroidQrCodeRenamedPackageNormalizerTest extends TestCase
{
    /**
     * @var EndroidQrCodeRenamedPackageNormalizer
     */
    private $normalizer;

    private $fixture = [
        'name'     => 'pimcore/pimcore',
        'type'     => 'project',
        'homepage' => 'http://www.pimcore.com',
        'license'  => 'GPL-3.0',
        'require'  => [
            'php'                  => '>=7.0',
            'endroid/qr-code'      => '~2.2',
            'pimcore/core-version' => 'v5.1.0',
            'symfony/symfony'      => '3.4.*',
        ],
    ];

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->normalizer = new EndroidQrCodeRenamedPackageNormalizer();
    }

    public function testNoChangesWithoutLegacyPackageName()
    {
        $this->assertEquals($this->fixture, $this->normalizer->normalize($this->fixture));
    }

    public function testOldPackageIsRemovedIfBothExist()
    {
        $input = $this->fixture;

        $input['require']['endroid/qrcode'] = '~2.2';

        codecept_debug($input);

        $this->assertEquals(
            $this->fixture,
            $this->normalizer->normalize($input)
        );
    }

    public function testPackageIsRenamedIfOnlyLegacyExists()
    {
        $input = $this->fixture;

        unset($input['require']['endroid/qr-code']);
        $input['require']['endroid/qrcode'] = '~2.2';

        codecept_debug($input);

        $this->assertEquals(
            $this->fixture,
            $this->normalizer->normalize($input)
        );
    }
}
