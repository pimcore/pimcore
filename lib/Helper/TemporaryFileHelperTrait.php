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

use Exception;
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
     * @throws Exception
     */
    protected static function getLocalFileFromStream(mixed $stream): string
    {
        if (!stream_is_local($stream) || (is_resource($stream) && stream_get_meta_data($stream)['uri'] === 'php://temp')) {
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
     * @throws Exception
     */
    protected static function getTemporaryFileFromStream(mixed $stream, bool $keep = false): string
    {
        if (is_string($stream)) {
            $src = fopen($stream, 'rb');
            $fileExtension = pathinfo($stream, PATHINFO_EXTENSION);
        } else {
            $src = $stream;
            $streamMeta = stream_get_meta_data($src);
            $fileExtension = pathinfo($streamMeta['uri'], PATHINFO_EXTENSION);
        }

        $tmpFilePath = File::getLocalTempFilePath($fileExtension, $keep);

        $dest = fopen($tmpFilePath, 'wb', false, File::getContext());
        if (!$dest) {
            throw new Exception(sprintf('Unable to create temporary file in %s', $tmpFilePath));
        }

        stream_copy_to_stream($src, $dest);
        fclose($dest);

        return $tmpFilePath;
    }
}
