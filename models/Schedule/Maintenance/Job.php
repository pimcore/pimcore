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

namespace Pimcore\Model\Schedule\Maintenance;

use Pimcore\Model\Tool\Lock;

/**
 * @deprecated Usage of Job is deprecated since Pimcore 5.7 and will be removed in 6.0. Please use Tagged Services now for Maintenance tasks
 */
class Job
{
    /**
     * By default, 24h expire timestamp.
     */
    const EXPIRE_TIMESTAMP = 86400;

    /**
     * @var string
     */
    private $id;

    /**
     * @var array
     */
    private $callable;

    /**
     * @var array
     */
    private $arguments = [];

    /**
     * @var int
     */
    private $expire = self::EXPIRE_TIMESTAMP;

    /**
     * @param string $id
     * @param mixed $callable
     * @param array|null $arguments
     * @param int $expire
     */
    public function __construct(string $id, $callable, array $arguments = [], int $expire = self::EXPIRE_TIMESTAMP)
    {
        $this->id = $id;
        $this->callable = $callable;
        $this->arguments = $arguments;
        $this->expire = $expire;

        trigger_error(
            'Usage of Job is deprecated since Pimcore 5.7 and will be removed in 6.0. Please use Tagged Services now for Maintenance tasks',
            E_USER_DEPRECATED
        );
    }

    public static function fromMethodCall(string $id, $object, string $method, array $arguments = [], int $expire = self::EXPIRE_TIMESTAMP): Job
    {
        return new static($id, [$object, $method], $arguments, $expire);
    }

    public static function fromClosure(string $id, \Closure $closure, array $arguments = [], int $expire = self::EXPIRE_TIMESTAMP): Job
    {
        return new static($id, $closure, $arguments, $expire);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLockKey(): string
    {
        return 'maintenance-job-' . $this->getId();
    }

    public function lock()
    {
        Lock::lock($this->getLockKey());
    }

    public function unlock()
    {
        Lock::release($this->getLockKey());
    }

    public function isLocked(): bool
    {
        return Lock::isLocked($this->getLockKey(), $this->expire);
    }

    public function execute()
    {
        if (!is_callable($this->callable)) {
            return null;
        }

        return call_user_func_array($this->callable, $this->arguments ?? []);
    }
}
