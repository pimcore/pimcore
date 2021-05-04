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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Twig\Extension;

use Pimcore\Document;
use Pimcore\File;
use Pimcore\Twig\Extension\Templating\PimcoreUrl;
use Pimcore\Video;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

/**
 * @internal
 */
class HelpersExtension extends AbstractExtension
{
    /**
     * @var PimcoreUrl
     */
    private $pimcoreUrlHelper;

    public function __construct(PimcoreUrl $pimcoreUrlHelper)
    {
        $this->pimcoreUrlHelper = $pimcoreUrlHelper;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('basename', [$this, 'basenameFilter']),
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('pimcore_video_is_available', [Video::class, 'isAvailable']),
            new TwigFunction('pimcore_document_is_available', [Document::class, 'isAvailable']),
            new TwigFunction('pimcore_file_exists', function ($file) {
                return is_file($file);
            }),
            new TwigFunction('pimcore_file_extension', [File::class, 'getFileExtension']),
            new TwigFunction('pimcore_image_version_preview', [$this, 'getImageVersionPreview']),
            new TwigFunction('pimcore_breach_attack_random_content', [$this, 'breachAttackRandomContent'], [
                'is_safe' => ['html'],
            ]),
            new TwigFunction('pimcore_url', $this->pimcoreUrlHelper, [
                'name' => 'pimcore_url',
                'is_safe' => null,
            ]),
        ];
    }

    public function getTests()
    {
        return [
            new TwigTest('instanceof', function ($object, $class) {
                return $object instanceof $class;
            }),
        ];
    }

    /**
     * @param string $value
     * @param string $suffix
     *
     * @return string
     */
    public function basenameFilter($value, $suffix = '')
    {
        return basename($value, $suffix);
    }

    /**
     * @param string $file
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getImageVersionPreview($file)
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
     * @return string
     *
     * @throws \Exception
     */
    public function breachAttackRandomContent()
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
}
