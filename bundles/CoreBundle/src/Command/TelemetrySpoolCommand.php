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

use Pimcore\Console\AbstractCommand;
use Pimcore\Telemetry\Relay\RelayClientInterface;
use Pimcore\Telemetry\Spool\TelemetryOutboxInterface;
use Pimcore\Telemetry\Spool\TelemetrySpool;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use function json_encode;

/**
 * Inspect, drain, or garbage-collect the telemetry spool (the durable outbox). In production the
 * drain also runs unattended from the maintenance job ({@see \Pimcore\Maintenance\Tasks\TelemetrySpoolDrainTask})
 * and from the Studio UI; `--drain` here forwards encrypted batches to the relay the same way, for
 * operators and local testing.
 *
 * @internal
 */
#[AsCommand(
    name: 'pimcore:telemetry:spool',
    description: 'Inspect, drain, or garbage-collect the telemetry spool (outbox)',
)]
class TelemetrySpoolCommand extends AbstractCommand
{
    public function __construct(
        private readonly TelemetrySpool $spool,
        private readonly TelemetryOutboxInterface $outbox,
        private readonly RelayClientInterface $relay,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('peek', null, InputOption::VALUE_REQUIRED, 'Print the N oldest pending events without leasing them')
            ->addOption('drain', null, InputOption::VALUE_NONE, 'Encrypt and forward pending batches to the relay (ack only on success)')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'With --drain: show pending events without leasing or forwarding')
            ->addOption('gc', null, InputOption::VALUE_NONE, 'Release expired leases and delete events past their TTL');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // The outbox table is provisioned by the installer or by migration Version20260720120000,
        // never at runtime. Say so plainly instead of surfacing a driver stack trace.
        if (!$this->spool->isProvisioned()) {
            $this->io->error(
                'The telemetry_spool table does not exist. Run "bin/console doctrine:migrations:migrate" '
                . '(or reinstall) to create it.'
            );

            return self::FAILURE;
        }

        if ($input->getOption('gc')) {
            $released = $this->spool->releaseExpiredClaims();
            $deleted = $this->spool->gc();
            $this->io->success("Spool GC: released $released expired lease(s), deleted $deleted expired event(s).");

            return self::SUCCESS;
        }

        if ($input->getOption('drain')) {
            return $input->getOption('dry-run') ? $this->dryRun() : $this->drain();
        }

        $this->io->title('Telemetry spool');
        $this->io->definitionList(
            ['pending' => (string)$this->spool->countPending()],
            ['claimed (in flight)' => (string)$this->spool->countClaimed()],
        );

        $peek = $input->getOption('peek');
        if ($peek !== null) {
            $events = $this->spool->peekPending((int)$peek);
            $this->io->section('Oldest pending events (not leased)');
            $this->io->writeln(json_encode($events, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        }

        return self::SUCCESS;
    }

    private function dryRun(): int
    {
        $events = $this->spool->peekPending(50);
        $this->io->title(sprintf('%d pending event(s) (dry-run, nothing leased or forwarded)', count($events)));
        $this->io->writeln(json_encode($events, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        return self::SUCCESS;
    }

    private function drain(): int
    {
        if (!$this->outbox->isReady()) {
            $this->io->warning('Outbox not ready (missing instance identifier or product key). Nothing drained.');

            return self::SUCCESS;
        }

        if (!$this->relay->isConfigured()) {
            $this->io->warning('No relay endpoint configured. Nothing drained.');

            return self::SUCCESS;
        }

        $forwarded = 0;

        while (($batch = $this->outbox->nextBatch()) !== null) {
            if (!$this->relay->send($batch->instanceIdentifier, $batch->ciphertext)) {
                $this->outbox->release($batch->nonce);
                $this->io->warning(sprintf('Forward failed after %d batch(es) - released the rest back to the spool for retry.', $forwarded));

                return self::FAILURE;
            }

            $this->outbox->ack($batch->nonce);
            $forwarded += $batch->count;
        }

        $this->io->success(sprintf('Drained and acked %d event(s) to the relay.', $forwarded));

        return self::SUCCESS;
    }
}
