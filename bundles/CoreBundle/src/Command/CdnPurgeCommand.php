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

use Pimcore\Cdn\AssetWebPath;
use Pimcore\Cdn\CdnAssetTag;
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
    description: 'Purge CDN cache for Pimcore assets and thumbnail configurations',
)]
class CdnPurgeCommand extends Command
{
    public function __construct(
        private readonly PurgeClientInterface $purgeClient,
        private readonly CdnAssetTag $assetTag,
        private readonly AssetWebPath $assetWebPath,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('asset', 'a', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Asset ID(s) — purges asset-{id} (all thumbnails) and asset-path-{hash} (original) tags. Loads the asset from the database to compute the path hash; unknown IDs are reported and skipped.')
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Thumbnail config name(s) — purges thumb-{config} tag for all assets using that config')
            ->setHelp(<<<'HELP'
Recovery and automation tool for invalidating Pimcore-specific CDN tags.

Routine purges happen automatically on asset save/update via the CDN purge
listener — you should not normally need this command. Use it for:

  * Recovery scenarios (manual flush after a queue failure or stale cache)
  * Automation (deploy hooks, content migration scripts, runbooks)
  * Computing Pimcore-specific surrogate tags (asset-path-{hash}) that are
    impractical to reproduce by hand in your CDN's admin panel.

For ad-hoc purges of arbitrary URLs or surrogate keys you already know,
prefer your CDN provider's admin panel — that is the simpler interface.

Purge all thumbnails (and the original) for asset ID 42:
  <info>pimcore:cdn:purge --asset 42</info>

Purge all assets using the "product-thumb" thumbnail config:
  <info>pimcore:cdn:purge --config product-thumb</info>

<comment>Note: Purge-all is not supported via this command.
To purge everything, use your CDN provider's admin panel or API directly.</comment>
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $assetIds = $input->getOption('asset');
        $configs = $input->getOption('config');

        if (empty($assetIds) && empty($configs)) {
            $io->error('At least one of --asset or --config must be provided.');

            return Command::FAILURE;
        }

        // Asset IDs must be positive integers. A non-numeric value (e.g. "foo") would
        // otherwise build a meaningless "asset-foo" tag and load asset 0. Fail fast on
        // malformed input rather than partially purging the valid IDs in the same call.
        $invalidIds = array_filter(
            $assetIds,
            static fn (string $id): bool => !ctype_digit($id) || (int) $id < 1,
        );
        if ($invalidIds !== []) {
            $io->error(sprintf(
                'Invalid asset ID(s): %s. Asset IDs must be positive integers.',
                implode(', ', $invalidIds),
            ));

            return Command::FAILURE;
        }

        $allTags = [];

        foreach ($assetIds as $id) {
            $id = (int) $id;
            $allTags[] = $this->assetTag->forAsset($id);

            // Also purge the original asset CDN entry via its path-derived tag (CdnAssetTag::forPath
            // owns the hash, shared with CdnSurrogateKeyListener / CdnPurgeListener).
            $asset = $this->loadAsset($id);
            if ($asset === null) {
                $io->warning(sprintf('Asset with ID "%s" not found — only the asset-%s thumbnail tag will be purged, the original CDN entry cannot be resolved without a path.', $id, $id));

                continue;
            }

            $allTags[] = $this->assetTag->forPath($this->assetWebPath->forFullPath($asset->getFullPath()));
        }

        foreach ($configs as $configName) {
            $allTags[] = $this->assetTag->forThumbConfig($configName);
        }

        // Deduplicate: callers may pass the same --asset or --config twice (e.g. via shell
        // expansion or scripted invocation), and an asset+config combination can produce the
        // same path-hash. Sending duplicate keys to Fastly wastes the per-request budget
        // (max 256 keys / batch purge) without changing the purge result.
        $allTags = array_values(array_unique($allTags));

        $io->writeln(sprintf('Purging %d tag(s): %s', count($allTags), implode(', ', $allTags)));
        $this->purgeClient->purgeByTags($allTags);

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
