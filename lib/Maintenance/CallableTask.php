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

namespace Pimcore\Maintenance;

final class CallableTask implements TaskInterface
{
    /**
     * @var callable
     */
    private $callable;

    /**
     * @var array
     */
    private $arguments;

    /**
     * @param       $callable
     * @param array $arguments
     */
    public function __construct($callable, $arguments = [])
    {
        $this->callable = $callable;
        $this->arguments = $arguments;
    }

    /**
     * @param callable $callable
     * @param array    $arguments
     *
     * @return CallableTask
     */
    public static function fromCallable(callable $callable, $arguments = [])
    {
        return new static($callable, $arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        return call_user_func_array($this->callable, $this->arguments);
    }
}
