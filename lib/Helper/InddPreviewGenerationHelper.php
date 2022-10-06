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
 *
 * pimcore/vendor/pimcore/pimcore/models/Asset/Image/Thumbnail/Processor.php:259-261
 * if (pathinfo($asset->getFilename(), PATHINFO_EXTENSION) == 'indd') {
InddPreviewGenerationHelper::generatePreviewImage($asset, $storagePath);
}
 *
 * pimcore/vendor/pimcore/pimcore/models/Asset.php:444
 * 'image' => ['/image/', "/\.eps$/", "/\.ai$/", "/\.svgz$/", "/\.pcx$/", "/\.iff$/", "/\.pct$/", "/\.wmf$/", '/photoshop/', "/\.indd/"],
 */
class InddPreviewGenerationHelper
{
    /**
     * @param Asset  $asset
     * @param string $storagePath
     *
     * @return void
     */
    public static function generatePreviewImage(Asset $asset, string $storagePath): void
    {
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

        $image = str_replace('base64:', '', reset($pageImage));
        file_put_contents($tmpPath, base64_decode($image));

        $storage = Storage::get('thumbnail');
        if (!file_exists($storage->fileExists(pathinfo($storagePath, PATHINFO_DIRNAME)))) {
            imagepng(imagecreatefromstring(file_get_contents($tmpPath)), $tmpPathPng);
            $storage->write($storagePath, file_get_contents($tmpPathPng));
        }

        unlink($tmpPath);
        unlink($tmpPathPng);
    }
}
