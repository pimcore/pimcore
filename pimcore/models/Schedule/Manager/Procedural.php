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
 * @category   Pimcore
 * @package    Schedule
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Schedule\Manager;

use Pimcore\Model;
use Pimcore\Model\Schedule\Maintenance\Job;
use Psr\Log\LoggerInterface;

class Procedural
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    protected $jobs = [];

    /**
     * @var array
     */
    protected $validJobs = [];

    /**
     * @var array
     */
    protected $excludedJobs = [];

    /**
     * @var string
     */
    protected $pidFileName;

    /**
     * @var bool
     */
    protected $force = false;

    /**
     * @param string $pidFileName
     * @param LoggerInterface $logger
     */
    public function __construct(string $pidFileName, LoggerInterface $logger)
    {
        $this->pidFileName = $pidFileName;
        $this->logger      = $logger;
    }

    /**
     * @param $validJobs
     *
     * @return $this
     */
    public function setValidJobs(array $validJobs)
    {
        $this->validJobs = $validJobs;

        return $this;
    }

    /**
     * @param $excludedJobs
     *
     * @return $this
     */
    public function setExcludedJobs(array $excludedJobs)
    {
        $this->excludedJobs = $excludedJobs;

        return $this;
    }

    /**
     * @param Job $job
     * @param bool $force
     *
     * @return bool
     */
    public function registerJob(Job $job, bool $force = false)
    {
        if (!empty($this->validJobs) and !in_array($job->getId(), $this->validJobs)) {
            $this->logger->info('Skipped job with ID {id} because it is not in the valid jobs', [
                'id' => $job->getId()
            ]);

            return false;
        }

        if (!empty($this->excludedJobs) and in_array($job->getId(), $this->excludedJobs)) {
            $this->logger->info('Skipped job with ID {id} because it has been excluded', [
                'id' => $job->getId()
            ]);

            return false;
        }

        if (!$job->isLocked() || $force || $this->getForce()) {
            $this->jobs[] = $job;

            $this->logger->info('Registered job with ID {id}', [
                'id' => $job->getId()
            ]);

            return true;
        } else {
            $this->logger->info('Skipped job with ID {id} because it is still locked', [
                'id' => $job->getId()
            ]);
        }

        return false;
    }

    public function run()
    {
        $this->setLastExecution();

        foreach ($this->jobs as $job) {
            if ($job->isLocked() && !$this->getForce()) {
                $this->logger->info('Skipped job with ID {id} because it already being executed', [
                    'id' => $job->getId()
                ]);

                continue;
            }

            $job->lock();

            $this->logger->info('Executing job with ID {id}', [
                'id' => $job->getId()
            ]);

            try {
                $job->execute();

                $this->logger->info('Finished job with ID {id}', [
                    'id' => $job->getId()
                ]);
            } catch (\Exception $e) {
                $this->logger->error('Failed to execute job with ID {id}: {exception}', [
                    'id'        => $job->getId(),
                    'exception' => $e
                ]);
            }

            $job->unlock();
        }
    }

    public function setLastExecution()
    {
        Model\Tool\Lock::lock($this->pidFileName);
    }

    /**
     * @return mixed
     */
    public function getLastExecution()
    {
        $lock = Model\Tool\Lock::get($this->pidFileName);
        if ($date = $lock->getDate()) {
            return $date;
        }
    }

    /**
     * @param bool $force
     */
    public function setForce(bool $force)
    {
        $this->force = $force;
    }

    /**
     * @return bool
     */
    public function getForce(): bool
    {
        return $this->force;
    }
}
