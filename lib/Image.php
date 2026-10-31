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

namespace Pimcore;

use Exception;
use finfo;
use Pimcore;
use Pimcore\Image\AdapterInterface;

final class Image
{
    /**
     * MIME types, as fileinfo reports them, of the formats getimagesize() reads.
     */
    private const GETIMAGESIZE_MIME_TYPES = [
        'image/apng',
        'image/avif',
        'image/bmp',
        'image/gif',
        'image/jp2',
        'image/jpeg',
        'image/jpm',
        'image/jpx',
        'image/png',
        'image/tiff',
        'image/vnd.adobe.photoshop',
        'image/vnd.microsoft.icon',
        'image/webp',
        'image/x-icon',
        'image/x-jp2-codestream',
        'image/x-ms-bmp',
    ];

    /**
     *
     * @throws Exception
     */
    public static function getInstance(): AdapterInterface
    {
        return Pimcore::getContainer()->get(AdapterInterface::class);
    }

    /**
     * getimagesize() for a file of any type, without reading an unsupported one into memory.
     *
     * getimagesize() recognises its formats by their first bytes. For any other file it
     * ends in its XBM check, which reads the file a line at a time - so a file without
     * line feeds (an EPS or AI file with classic Mac CR line endings, for one) is read
     * into memory whole, and a large one is a fatal "Allowed memory size exhausted"
     * rather than a false return. The type is therefore identified from the first bytes,
     * and getimagesize() only runs for the formats it can read.
     *
     * @internal
     *
     * @param array<string, string>|null $imageInfo receives the APP markers, as getimagesize()'s second argument
     *
     * @return array<int|string, mixed>|false
     */
    public static function getImageSize(string $path, ?array &$imageInfo = null): array|false
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return false;
        }
        $head = fread($handle, 8192);
        fclose($handle);

        if (!$head || !in_array((new finfo(FILEINFO_MIME_TYPE))->buffer($head), self::GETIMAGESIZE_MIME_TYPES, true)) {
            return false;
        }

        return @getimagesize($path, $imageInfo);
    }
}
