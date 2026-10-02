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

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use Pimcore\Console\AbstractCommand;
use Pimcore\Telemetry\Snapshot\SnapshotBuilder;
use Pimcore\Telemetry\TelemetryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function json_encode;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:telemetry:send-snapshot',
    description: 'Build the deployment snapshot and enqueue it for delivery to the telemetry relay',
)]
class TelemetrySendSnapshotCommand extends AbstractCommand
{
    private const EVENT_INSTANCE_SNAPSHOT = 'instance.snapshot';

    private const INSTANCE_GROUP_TYPE = 'instance';

    public function __construct(
        private readonly TelemetryInterface $telemetry,
        private readonly SnapshotBuilder $builder,
        private readonly string $instanceIdentifier,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Print the snapshot that would be sent and exit without contacting PostHog'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $snapshot = $this->builder->build();

        if ($input->getOption('dry-run')) {
            $this->io->title('Telemetry snapshot (dry-run, nothing sent)');
            $this->io->writeln(json_encode($snapshot, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        }

        if (!$this->telemetry->isEnabled()) {
            $this->io->warning(
                'Telemetry is inactive: this instance needs both an instance identifier '
                . '(PIMCORE_INSTANCE_IDENTIFIER) and a product key (PIMCORE_PRODUCT_KEY). Nothing was enqueued.'
            );

            return self::SUCCESS;
        }

        $this->telemetry->groupIdentify(self::INSTANCE_GROUP_TYPE, $this->instanceIdentifier, $snapshot);
        $this->telemetry->capture(self::EVENT_INSTANCE_SNAPSHOT, $snapshot);
        $this->telemetry->flush();

        // Transport-neutral: "flushed" means handed to the configured transport - posted to the
        // relay (http) or persisted to the outbox for the UI to drain (spool).
        $this->io->success('Telemetry snapshot flushed.');

        return self::SUCCESS;
    }
}
