<?php
namespace Pimcore\Helper;

use Pimcore\File;

trait TemporaryFileHelperTrait
{
    /**
     * Get local file path of the given file or URL
     * @param string $path file path or URL
     * @return string path to local file
     */
    private function getLocalFile($path): string {
        if (!stream_is_local($path)) {
            $tmpFilename = 'tmpfile_'.md5($path).'.'.File::getFileExtension($path);
            $tmpFilePath = PIMCORE_SYSTEM_TEMP_DIRECTORY.'/'.$tmpFilename;

            register_shutdown_function(static function() use ($tmpFilePath) {
                @unlink($tmpFilePath);
            });

            $src = fopen($path, 'rb');
            $dest = fopen($tmpFilePath, 'wb', false, File::getContext());
            stream_copy_to_stream($src, $dest);
            fclose($dest);

            $path = $tmpFilePath;
        }

        return $path;
    }
}
