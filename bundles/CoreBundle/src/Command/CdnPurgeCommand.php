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

namespace Pimcore\Bundle\CoreBundle\Command;

use Pimcore\Cdn\PurgeClientInterface;
use Pimcore\Model\Asset;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'pimcore:cdn:purge',
    description: 'Purge CDN cache by asset ID, thumbnail config, surrogate tag, or URL',
)]
class CdnPurgeCommand extends Command
{
    public function __construct(private readonly PurgeClientInterface $purgeClient)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('asset', 'a', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Asset ID(s) — purges asset-{id} (all thumbnails) and asset-path-{hash} (original) tags. Loads the asset from the database to compute the path hash; unknown IDs are reported and skipped.')
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Thumbnail config name(s) — purges thumb-{config} tag for all assets using that config')
            ->addOption('tag', 't', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Arbitrary surrogate-key tag(s)')
            ->addOption('url', 'u', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Full URL(s) to purge')
            ->setHelp(<<<'HELP'
Directly invokes the configured CDN purge client (bypasses the message queue).

Purge all thumbnails for asset ID 42:
  <info>pimcore:cdn:purge --asset 42</info>

Purge all assets using the "product-thumb" thumbnail config:
  <info>pimcore:cdn:purge --config product-thumb</info>

Purge a specific tag:
  <info>pimcore:cdn:purge --tag asset-42-thumb-product-thumb</info>

Purge by URL:
  <info>pimcore:cdn:purge --url https://cdn.example.com/var/assets/image.jpg</info>

<comment>Note: Purge-all is not supported via this command.
To purge everything, use your CDN provider's dashboard or API directly.</comment>
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $assetIds = $input->getOption('asset');
        $configs = $input->getOption('config');
        $tags = $input->getOption('tag');
        $urls = $input->getOption('url');

        if (empty($assetIds) && empty($configs) && empty($tags) && empty($urls)) {
            $io->error('At least one of --asset, --config, --tag, or --url must be provided.');

            return Command::FAILURE;
        }

        $allTags = [];

        foreach ($assetIds as $id) {
            $allTags[] = 'asset-' . $id;

            // Also purge the original asset CDN entry via path hash.
            // Identical hashing to CdnSurrogateKeyListener / CdnPurgeListener: sha256 of '/var/assets' + full path, first 12 hex chars.
            $asset = $this->loadAsset((int) $id);
            if ($asset === null) {
                $io->warning(sprintf('Asset with ID "%s" not found — only the asset-%s thumbnail tag will be purged, the original CDN entry cannot be resolved without a path.', $id, $id));

                continue;
            }

            $assetWebPath = '/var/assets' . $asset->getFullPath();
            $pathHash = substr(hash('sha256', $assetWebPath), 0, 12);
            $allTags[] = 'asset-path-' . $pathHash;
        }

        foreach ($configs as $configName) {
            $allTags[] = 'thumb-' . $configName;
        }

        foreach ($tags as $tag) {
            $allTags[] = $tag;
        }

        if (!empty($allTags)) {
            $io->writeln(sprintf('Purging %d tag(s): %s', count($allTags), implode(', ', $allTags)));
            $this->purgeClient->purgeByTags($allTags);
        }

        foreach ($urls as $url) {
            $io->writeln('Purging URL: ' . $url);
            $this->purgeClient->purgeByUrl($url);
        }

        $io->success('CDN purge dispatched.');

        return Command::SUCCESS;
    }

    /**
     * Loads an asset by ID. Extracted as a seam so unit tests can stub asset lookup
     * without bootstrapping the database.
     */
    protected function loadAsset(int $id): ?Asset
    {
        return Asset::getById($id);
    }
}
