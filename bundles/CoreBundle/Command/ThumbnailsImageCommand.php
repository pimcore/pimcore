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

namespace Pimcore\Bundle\CoreBundle\Command;

use Pimcore\Console\AbstractCommand;
use Pimcore\Model\Asset;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozarts\Console\Parallelization\Parallelization;

class ThumbnailsImageCommand extends AbstractCommand
{
    use Parallelization;

    protected static $defaultName = 'pimcore:thumbnails:image';

    /**
     * @var Asset\Image\Thumbnail\Config[] $thumbnailsToGenerate
     */
    private $thumbnailConfigNames = [];

    protected function configure()
    {
        $this
            ->setName('pimcore:thumbnails:image')
            ->setAliases(['thumbnails:image'])
            ->setDescription('Generate image thumbnails, useful to pre-generate thumbnails in the background')
            ->addOption(
                'parent',
                'p',
                InputOption::VALUE_OPTIONAL,
                'only create thumbnails of images in this folder (ID)'
            )
            ->addOption(
                'thumbnails',
                't',
                InputOption::VALUE_OPTIONAL,
                'only create specified thumbnails (comma separated eg.: thumb1,thumb2)'
            )->addOption(
                'system',
                's',
                InputOption::VALUE_NONE,
                'create system thumbnails (used for tree-preview, ...)'
            )->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'recreate thumbnails, regardless if they exist already'
            )->addOption(
                'skip-webp',
                null,
                InputOption::VALUE_NONE,
                'if target image format is set to auto in config, do not generate WEBP images for them'
            )->addOption(
                'skip-medias',
                null,
                InputOption::VALUE_NONE,
                'if config has media queries defined, do not generate thumbnails for them'
            )->addOption(
                'skip-high-res',
                null,
                InputOption::VALUE_NONE,
                'do not generate high-res (@2x) versions of thumbnails'
            )->addOption(
                'processes',
                null,
                InputOption::VALUE_OPTIONAL,
                'The number of parallel processes to run',
                1
            )->addOption(
                'child',
                null,
                InputOption::VALUE_NONE,
                'Set on child processes'
            )->addArgument(
                'item',
                InputArgument::OPTIONAL,
                'The item to process'
            );
    }

    /**
     * @inheritdoc
     *
     */
    protected function fetchItems(InputInterface $input): array
    {
        $list = new Asset\Image\Thumbnail\Config\Listing();
        $thumbnailConfigList = $list->getThumbnails();

        $allowedThumbs = [];
        if ($input->getOption('thumbnails')) {
            $allowedThumbs = explode(',', $input->getOption('thumbnails'));
        }

        /**
         * @var Asset\Image\Thumbnail\Config[] $thumbnailsToGenerate
         */
        $thumbnailsToGenerate = [];

        foreach ($thumbnailConfigList as $thumbnailConfig) {
            if (empty($allowedThumbs) || in_array($thumbnailConfig->getName(), $allowedThumbs)) {
                $medias = array_merge(['default' => 'defaultMedia'], $thumbnailConfig->getMedias() ?: []);
                foreach ($medias as $mediaName => $media) {
                    $configMedia = clone $thumbnailConfig;
                    if ($mediaName !== 'default') {
                        $configMedia->selectMedia($mediaName);
                    }

                    if ($input->getOption('skip-medias') && $mediaName !== 'default') {
                        continue;
                    }

                    $resolutions = [1, 2];
                    if ($input->getOption('skip-high-res')) {
                        $resolutions = [1];
                    }

                    foreach ($resolutions as $resolution) {
                        $resConfig = clone $configMedia;
                        $resConfig->setHighResolution($resolution);
                        $thumbnailsToGenerate[] = $resConfig;

                        if (!$input->getOption('skip-webp') && $resConfig->getFormat() === 'SOURCE') {
                            $webpConfig = clone $resConfig;
                            $webpConfig->setFormat('webp');
                            $thumbnailsToGenerate[] = $webpConfig;
                        }
                    }
                }
            }
        }

        if ($input->getOption('system')) {
            if (!$input->getOption('thumbnails')) {
                $thumbnailsToGenerate = [];
            }
            $thumbnailsToGenerate[] = Asset\Image\Thumbnail\Config::getPreviewConfig();
        } elseif (!$input->getOption('thumbnails')) {
            $thumbnailsToGenerate[] = Asset\Image\Thumbnail\Config::getPreviewConfig();
        }

        // get only images
        $conditions = ["type = 'image'"];

        if ($input->getOption('parent')) {
            $parent = Asset::getById($input->getOption('parent'));
            if ($parent instanceof Asset\Folder) {
                $conditions[] = "path LIKE '".$parent->getRealFullPath()."/%'";
            } else {
                $this->writeError($input->getOption('parent').' is not a valid asset folder ID!');
                exit;
            }
        }

        $list = new Asset\Listing();
        $list->setCondition(implode(' AND ', $conditions));

        $items = [];
        foreach ($list as $image) {
            $clearedThumbnails = [];
            foreach ($thumbnailsToGenerate as $thumbnailConfig) {
                if ($input->getOption('force') && !isset($clearedThumbnails[$thumbnailConfig->getName()])) {
                    $image->clearThumbnail($thumbnailConfig->getName());
                    $clearedThumbnails[$thumbnailConfig->getName()] = true;
                }

                $items[] = serialize([
                    'image_id' => $image->getId(),
                    'thumbnail' => $thumbnailConfig
                ]);
            }
        }
        return $items;
    }

    /**
     * @inheritdoc
     *
     */
    protected function runSingleCommand(string $item, InputInterface $input, OutputInterface $output)
    {
        $item = unserialize($item);

        $image = Asset\Image::getById($item['image_id']);
        if (!$image instanceof Asset\Image) {
            return;
        }

        $thumbnail = $item['thumbnail'] ?? null;
        if(!$thumbnail instanceof Asset\Image\Thumbnail\Config) {
            return;
        }

        $thumbnail = $image->getThumbnail($thumbnail);


        if ($output->isVerbose()) {
            $output->writeln(
                sprintf(
                    'generated thumbnail for image [%d] | file: %s',
                    $image->getId(),
                    $thumbnail->getPath(false)
                )
            );
        } else {
            $thumbnail->getPath(false);
        }
    }

    /**
     * @inheritdoc
     *
     */
    protected function runAfterBatch(InputInterface $input, OutputInterface $output)
    {
        \Pimcore::collectGarbage();
    }

    /**
     * @inheritdoc
     *
     */
    protected function getItemName(int $count): string
    {
        return 1 === $count ? 'thumbnail' : 'thumbnails';
    }

    /**
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private function getContainer()
    {
        return \Pimcore::getContainer();
    }
}
