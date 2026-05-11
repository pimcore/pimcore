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

namespace Pimcore\Bundle\CoreBundle\Command\Asset;

use Pimcore;
use Pimcore\Console\AbstractCommand;
use Pimcore\Model\Asset;
use Pimcore\Model\Element\Service as ElementService;
use Pimcore\Tool\Storage;
use Pimcore\Helper\MimeTypeHelper;
use Pimcore\Cache\RuntimeCache;
use League\Flysystem\FilesystemException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:assets:rewrite-mime-type',
    description: 'Rewrites the MIME type of assets. Note: versions are created under the user executing this command; run as the server user (e.g. www-data) to avoid permission issues.',
)]
class RewriteMimeTypeCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addArgument('ids', InputOption::VALUE_OPTIONAL, 'Comma-separated list of asset IDs')
            ->addOption(
                'create-version',
                'c',
                InputOption::VALUE_NONE,
                'Create a new version for the asset'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $conditions = ["`type` != 'folder'"];

        if ($idsArg = $input->getArgument('ids')) {
            $ids = array_map('intval', explode(',', $idsArg[0]));
            $conditions []= sprintf('id IN (%s)', implode(',', $ids));
        }

        $createVersion = $input->getOption('create-version');

        $list = new Asset\Listing();
        $list->setCondition(implode(' AND ', $conditions));
        $total = $list->getTotalCount();
        $perLoop = 10;

        for ($i = 0; $i < (ceil($total / $perLoop)); $i++) {
            $list->setLimit($perLoop);
            $list->setOffset($i * $perLoop);
            $assets = $list->load();
            foreach ($assets as $asset) {
                $output->writeln($asset->getFullPath());

                $storage = Storage::get('asset');
                $path = $asset->getRealFullPath();
                $mimeType = null;
                $typeChanged = false;
                try {
                    $mimeType = $storage->mimeType($path);
                } catch (FilesystemException $e) {
                    // ignore, fallback
                }

                if (!$mimeType || $mimeType === 'application/octet-stream') {
                     try {
                         $src = $asset->getStream();
                         $mimeType = (new MimeTypeHelper())->guessMimeType($src) ?? 'application/octet-stream';
                     } finally {
                         $asset->setStream(null);
                     }
                }

                if ($asset->getMimeType() === $mimeType) {
                    $output->writeln($asset->getFullPath() . ' - MIME type is already correct, skipping');
                    continue;
                }

                $asset->setMimeType($mimeType);

                // set type
                $type = Asset::getTypeFromMimeMapping($mimeType, $asset->getFilename());
                if ($type !== $asset->getType()) {
                    $asset->setType($type);
                    $typeChanged = true;
                }

                $asset->getDao()->update();

                // set asset to registry
                $cacheKey = ElementService::getElementCacheTag('asset', $asset->getId());
                RuntimeCache::set($cacheKey, $asset);
                if ($typeChanged) {
                    // get concrete type of asset
                    // this is important because at the time of creating an asset it's not clear which type (resp. class) it will have
                    // the type (image, document, ...) depends on the mime-type
                    RuntimeCache::set($cacheKey, null);
                    Asset::getById($asset->getId()); // call it to load it to the runtime cache again
                }


                if ($createVersion) {
                    $asset->saveVersion(false, true, 'change MIME-Type');
                }
            }


            Pimcore::collectGarbage();
        }

        return 0;
    }
}
