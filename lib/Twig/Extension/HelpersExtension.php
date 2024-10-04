<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Twig\Extension;

use Exception;
use Pimcore\Document;
use Pimcore\Twig\Extension\Templating\PimcoreUrl;
use Pimcore\Video;
use Symfony\Component\Mime\MimeTypes;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

/**
 * @internal
 */
class HelpersExtension extends AbstractExtension
{
    private PimcoreUrl $pimcoreUrlHelper;

    public function __construct(PimcoreUrl $pimcoreUrlHelper)
    {
        $this->pimcoreUrlHelper = $pimcoreUrlHelper;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('basename', [$this, 'basenameFilter']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('pimcore_video_is_available', [Video::class, 'isAvailable']),
            new TwigFunction('pimcore_document_is_available', [Document::class, 'isAvailable']),
            new TwigFunction('pimcore_file_exists', function ($file) {
                return is_file($file);
            }),
            new TwigFunction('pimcore_file_extension', [$this, 'getFileExtension']),
            new TwigFunction('pimcore_image_version_preview', [$this, 'getImageVersionPreview']),
            new TwigFunction('pimcore_asset_version_preview', [$this, 'getAssetVersionPreview']),
            new TwigFunction('pimcore_breach_attack_random_content', [$this, 'breachAttackRandomContent'], [
                'is_safe' => ['html'],
            ]),
            new TwigFunction('pimcore_url', $this->pimcoreUrlHelper, [
                'name' => 'pimcore_url',
                'is_safe' => null,
            ]),
        ];
    }

    public function getTests(): array
    {
        return [
            new TwigTest('instanceof', function ($object, $class) {
                return $object instanceof $class;
            }),
        ];
    }

    public function basenameFilter(string $value, string $suffix = ''): string
    {
        return basename($value, $suffix);
    }

    /**
     *
     *
     * @throws Exception
     */
    public function getImageVersionPreview(string $file): string
    {
        $thumbnail = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/image-version-preview-' . uniqid() . '.png';
        $convert = \Pimcore\Image::getInstance();
        $convert->load($file);
        $convert->contain(500, 500);
        $convert->save($thumbnail, 'png');

        $dataUri = 'data:image/png;base64,' . base64_encode(file_get_contents($thumbnail));
        unlink($thumbnail);
        unlink($file);

        return $dataUri;
    }

    /**
     * @throws Exception
     */
    public function getAssetVersionPreview(string $file): string
    {
        $dataUri = 'data:'.MimeTypes::getDefault()->guessMimeType($file).';base64,'.base64_encode(file_get_contents($file));
        unlink($file);

        return $dataUri;
    }

    /**
     *
     * @throws Exception
     */
    public function breachAttackRandomContent(): string
    {
        $length = 50;
        $randomData = random_bytes($length);

        return '<!--'
            . substr(
                base64_encode($randomData),
                0,
                ord($randomData[$length - 1]) % 32
            )
            . '-->';
    }

    public function getFileExtension(string $fileName): string
    {
        return pathinfo($fileName, PATHINFO_EXTENSION);
    }
}
