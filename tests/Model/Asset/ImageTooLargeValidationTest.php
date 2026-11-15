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

use Pimcore\Model\Asset;
use Pimcore\Model\Element\StructuredValidationException;
use Pimcore\Model\Element\ValidationMessageKey;
use Pimcore\Tests\Support\Test\ModelTestCase;

/**
 * An image above the configured maximum of pixels is rejected with a keyed validation error (pimcore/pimcore#8846).
 *
 * @group model.asset.asset
 */
class ImageTooLargeValidationTest extends ModelTestCase
{
    public function testImageAboveMaxPixelsIsRejectedWithKeyAndParameters(): void
    {
        // 8000 x 6000 = 48 megapixels, the default limit is 40 megapixels; a one-colour PNG stays small
        $image = imagecreate(8000, 6000);
        $this->assertNotFalse($image);
        imagecolorallocate($image, 255, 255, 255);
        ob_start();
        imagepng($image, null, 9);
        $png = (string) ob_get_clean();

        try {
            Asset::create(1, ['filename' => 'pme_too_large.png', 'data' => $png]);
            $this->fail('Expected a StructuredValidationException');
        } catch (StructuredValidationException $exception) {
            $this->assertStringContainsString('pme_too_large.png', $exception->getMessage());
            $this->assertSame(ValidationMessageKey::IMAGE_TOO_LARGE->value, $exception->getTranslationKey());
            $this->assertSame(
                [
                    'filename' => 'pme_too_large.png',
                    'max_megapixels' => 40,
                    'suggested_width' => 5400,
                    'suggested_height' => 4000,
                ],
                $exception->getTranslationParameters()
            );
        }
    }
}
