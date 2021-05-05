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

namespace Pimcore\Image;

use Pimcore\Exception\ImageOptimizationFailedException;
use Pimcore\File;
use Pimcore\Image\Optimizer\OptimizerInterface;
use Pimcore\Tool\Storage;

class Optimizer implements ImageOptimizerInterface
{
    /**
     * @var OptimizerInterface[]
     */
    private $optimizers = [];

    /**
     * {@inheritdoc}
     */
    public function optimizeImage($path)
    {
        $extension = File::getFileExtension($path);
        $storage = Storage::get('thumbnail');
        $optimizedImages = [];
        $workingPath = File::getLocalTempFilePath($extension);
        file_put_contents($workingPath, $storage->read($path));

        foreach ($this->optimizers as $optimizer) {
            if ($optimizer->supports($storage->mimeType($path))) {
                try {
                    $optimizedFile = $optimizer->optimizeImage($workingPath, File::getLocalTempFilePath($extension));

                    $optimizedImages[] = [
                        'filesize' => filesize($optimizedFile),
                        'path' => $optimizedFile,
                        'optimizer' => $optimizer,
                    ];
                } catch (ImageOptimizationFailedException $ex) {
                }
            }
        }

        // order by filesize
        usort($optimizedImages, function ($a, $b) {
            if ($a['filesize'] == $b['filesize']) {
                return 0;
            }

            return ($a['filesize'] < $b['filesize']) ? -1 : 1;
        });

        // first entry is the smallest -> use this one
        if (count($optimizedImages)) {
            $storage->write($path, file_get_contents($optimizedImages[0]['path']));
        }

        // cleanup
        foreach ($optimizedImages as $tmpFile) {
            unlink($tmpFile['path']);
        }

        if (!stream_is_local($path)) {
            unlink($workingPath);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function registerOptimizer(OptimizerInterface $optimizer)
    {
        if (in_array($optimizer, $this->optimizers)) {
            throw new \InvalidArgumentException(sprintf('Optimizer of class %s has already been registered',
                get_class($optimizer)));
        }

        $this->optimizers[] = $optimizer;
    }
}
