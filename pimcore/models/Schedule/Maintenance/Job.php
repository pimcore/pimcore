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

class Job
{
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
     * @param string $id
     * @param mixed $callable
     * @param array|null $arguments
     */
    public function __construct(string $id, $callable, array $arguments = [])
    {
        $this->id = $id;
        $this->callable = $callable;
        $this->arguments = $arguments;
    }

    public static function fromMethodCall(string $id, $object, string $method, array $arguments = []): Job
    {
        return new static($id, [$object, $method], $arguments);
    }

    public static function fromClosure(string $id, \Closure $closure, array $arguments = []): Job
    {
        return new static($id, $closure, $arguments);
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
        return Lock::isLocked($this->getLockKey(), 86400); // 24h expire
    }

    public function execute()
    {
        if (!is_callable($this->callable)) {
            return null;
        }

        return call_user_func_array($this->callable, $this->arguments ?? []);
    }
}
