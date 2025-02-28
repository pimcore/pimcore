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

namespace Pimcore\Helper;

use finfo;
use InvalidArgumentException;
use Symfony\Component\Mime\MimeTypes;

final class MimeTypeHelper implements MimeTypeHelperInterface
{
    public static function guessMimeTypeFromFile(string $filePath): ?string
    {
        return MimeTypes::getDefault()->guessMimeType($filePath);
    }

    public static function guessMimeTypeFromStream(mixed $stream): ?string
    {
        $fpPosition = 0;

        if (!is_resource($stream) || get_resource_type($stream) !== 'stream') {
            throw new InvalidArgumentException('The provided stream is not a valid stream resource.');
        }

        $seekable = stream_get_meta_data($stream)['seekable'] ?? false;
        if($seekable) {
            $fpPosition = ftell($stream);
            fseek($stream, 0);
        }

        $magicBytes = fread($stream, 1024);

        if($seekable &&
            $fpPosition !== false
        ) {
            fseek($stream, $fpPosition);
        }

        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo->buffer($magicBytes);

        return $mimeType === false ? null : $mimeType;
    }
}
