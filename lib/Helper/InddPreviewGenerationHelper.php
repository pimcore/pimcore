<?php
/**
 * Created by PhpStorm.
 * User: jneugebauer
 * Date: 06.10.22
 * Time: 09:11
 */

namespace Pimcore\Helper;

use Pimcore\Model\Asset;
use Pimcore\Tool\Storage;
use Symfony\Component\Process\Process;

/**
 * Generates Indd Preview Images
 */
class InddPreviewGenerationHelper
{
    /**
     * @param Asset  $asset
     * @param string $storagePath
     *
     * @return void
     * @throws \Exception
     */
    public static function generatePreviewImage(Asset $asset, string $storagePath): void
    {
        try {
            $exiftool = \Pimcore\Tool\Console::getExecutable('exiftool');
            $filePath = $asset->getLocalFile();
            $process = new Process([$exiftool, '-b', '-json', $filePath]);
            $process->run();
            $output = $process->getOutput();
            $embeddedMetaData = json_decode($output);
            $pageImage = reset($embeddedMetaData)->PageImage;

            $tmpPath = sprintf('%s/var/tmp/indd_%s.jpg', PIMCORE_WEB_ROOT, $asset->getFilename());
            $tmpPathPng = sprintf('%s/var/tmp/indd_%s.png', PIMCORE_WEB_ROOT, $asset->getFilename());
            if (file_exists($tmpPath)) {
                unlink($tmpPath);
            }

            if (is_array($pageImage)) {
                $pageImage = reset($pageImage);
            }

            $pageImage = str_replace('base64:', '', $pageImage);
            file_put_contents($tmpPath, base64_decode($pageImage));

            $storage = Storage::get('thumbnail');
            imagepng(imagecreatefromstring(file_get_contents($tmpPath)), $tmpPathPng);
            $storage->write($storagePath, file_get_contents($tmpPathPng));

            unlink($tmpPath);
            unlink($tmpPathPng);
        } catch (Exception $e) {

        }
    }
}
