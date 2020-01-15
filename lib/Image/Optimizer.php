<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Image;

use Pimcore\Exception\ImageOptimizationFailedException;
use Pimcore\Image\Optimizer\OptimizerInterface;

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
        $optimizedImages = [];
        $workingPath = $path;

        if (!stream_is_local($path)) {
            $workingPath = $this->createOutputImage();
            copy($path, $workingPath);
        }

        $extension = pathinfo($workingPath, PATHINFO_EXTENSION);

        foreach ($this->optimizers as $optimizer) {
            if ($optimizer->supports($path)) {
                try {
                    $optimizedFile = $optimizer->optimizeImage($workingPath, $this->createOutputImage($extension));

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
            copy($optimizedImages[0]['path'], $path);
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

    /**
     * @param string|null $type
     *
     * @return string
     */
    private function createOutputImage($type = null): string
    {
        $file = PIMCORE_SYSTEM_TEMP_DIRECTORY.'/'.uniqid('optimize', true);
        if ($type) {
            $file .= '.'.$type;
        }

        return $file;
    }

    /**
     * @param string $path
     */
    public static function optimize($path)
    {
        @trigger_error(
            'Usage of Pimcore\Image\Optimizer::optimize is deprecated and will be removed with Pimcore 7.0. Please use the Service: Pimcore\Image\Optimizer instead.',
            E_USER_DEPRECATED
        );

        \Pimcore::getContainer()->get(Optimizer::class)->optimizeImage($path);
    }
}
