<?php

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

namespace Pimcore\Helper;

use Pimcore\File;

/**
 * @internal
 */
trait TemporaryFileHelperTrait
{
    /**
     * Get local file path of the given file or URL
     *
     * @param string|resource $stream local path, wrapper or file handle
     *
     * @return string path to local file
     *
     * @throws \Exception
     */
    protected static function getLocalFileFromStream($stream): string
    {
        if (!stream_is_local($stream)) {
            $stream = self::getTemporaryFileFromStream($stream);
        }

        if (is_resource($stream)) {
            $streamMeta = stream_get_meta_data($stream);
            $stream = $streamMeta['uri'];
        }

        return $stream;
    }

    /**
     * @param resource|string $stream
     * @param bool $keep whether to delete this file on shutdown or not
     *
     * @return string
     *
     * @throws \Exception
     */
    protected static function getTemporaryFileFromStream($stream, bool $keep = false): string
    {
        if (is_string($stream)) {
            $src = fopen($stream, 'rb');
            $fileExtension = File::getFileExtension($stream);
        } else {
            $src = $stream;
            $streamMeta = stream_get_meta_data($src);
            $fileExtension = File::getFileExtension($streamMeta['uri']);
        }

        $tmpFilePath = File::getLocalTempFilePath($fileExtension);

        $dest = fopen($tmpFilePath, 'wb', false, File::getContext());
        if (!$dest) {
            throw new \Exception(sprintf('Unable to create temporary file in %s', $tmpFilePath));
        }

        stream_copy_to_stream($src, $dest);
        fclose($dest);

        if (!$keep) {
            register_shutdown_function(static function () use ($tmpFilePath) {
                @unlink($tmpFilePath);
            });
        }

        return $tmpFilePath;
    }
}
