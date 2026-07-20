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

namespace Pimcore\Helper;

use finfo;
use InvalidArgumentException;
use Symfony\Component\Mime\MimeTypes;

final class MimeTypeHelper implements MimeTypeHelperInterface
{
    /**
     * @param string|resource $file
     */
    public function guessMimeType(mixed $file): ?string
    {
        if (is_string($file)) {
            return $this->guessMimeTypeFromFile($file);
        }

        if (is_resource($file) && get_resource_type($file) === 'stream') {
            return $this->guessMimeTypeFromStream($file);
        }

        throw new InvalidArgumentException('The provided file must be a string or a stream resource.');
    }

    private function guessMimeTypeFromFile(string $filePath): ?string
    {
        return MimeTypes::getDefault()->guessMimeType($filePath);
    }

    private function guessMimeTypeFromStream(mixed $stream): ?string
    {
        $fpPosition = false;
        $metadata = stream_get_meta_data($stream);
        $seekable = $metadata['seekable'];
        if ($seekable) {
            $fpPosition = ftell($stream);
            fseek($stream, 0);
        }

        $bytes = fread($stream, 8192);

        if ($seekable &&
            $fpPosition !== false
        ) {
            fseek($stream, $fpPosition);
        }

        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo->buffer($bytes);

        // Fallback to extension-based guessing
        if (!$mimeType || $mimeType === 'application/octet-stream') {
            $extension = $metadata['uri'] ? pathinfo($metadata['uri'], PATHINFO_EXTENSION) : null;

            if ($extension) {
                $extension = strtolower($extension);
                $mimeTypes = new MimeTypes();
                $mimeType = $mimeTypes->getMimeTypes($extension)[0];
            }
        }

        return $mimeType === false ? null : $mimeType;
    }
}
