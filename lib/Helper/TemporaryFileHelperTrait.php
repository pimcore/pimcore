<?php

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
    private function getLocalFile($stream): string
    {
        if (!stream_is_local($stream)) {
            $stream = $this->getTemporaryFileFromStream($stream);
        }

        if (is_resource($stream)) {
            $streamMeta = stream_get_meta_data($stream);
            $stream = $streamMeta['uri'];
        }

        return $stream;
    }

    /**
     * @param resource|string $stream
     *
     * @return string
     *
     * @throws \Exception
     */
    private function getTemporaryFileFromStream($stream): string
    {
        if (is_string($stream)) {
            $src = fopen($stream, 'rb');
            $fileExtension = File::getFileExtension($stream);
        } else {
            $src = $stream;
            $streamMeta = stream_get_meta_data($src);
            $fileExtension = File::getFileExtension($streamMeta['uri']);
        }

        $tmpFilePath = sprintf('%s/temp-file-%s.%s',
            PIMCORE_SYSTEM_TEMP_DIRECTORY,
            uniqid() . '-' .  bin2hex(random_bytes(15)),
            $fileExtension
        );

        $dest = fopen($tmpFilePath, 'wb', false, File::getContext());
        if (!$dest) {
            throw new \Exception(sprintf('Unable to create temporary file in %s', $tmpFilePath));
        }

        stream_copy_to_stream($src, $dest);
        fclose($dest);

        register_shutdown_function(static function () use ($tmpFilePath) {
            @unlink($tmpFilePath);
        });

        return $tmpFilePath;
    }
}
