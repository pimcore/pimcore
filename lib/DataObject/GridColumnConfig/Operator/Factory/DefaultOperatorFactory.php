<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\DataObject\GridColumnConfig\Operator\Factory;

use Pimcore\DataObject\GridColumnConfig\Operator\OperatorInterface;
use Pimcore\Logger;

class DefaultOperatorFactory implements OperatorFactoryInterface
{
    /**
     * @var string
     */
    private $className;

    /**
     * @param string $className
     */
    public function __construct(string $className)
    {
        $this->className = $className;
    }

    /**
     * {@inheritdoc}
     */
    public function build(\stdClass $configElement, array $context = [])
    {
        if (class_exists($this->className)) {
            return new $this->className($configElement, $context);
        }

        Logger::warn('operator ' . $this->className . ' does not exist');

        return null;
    }
}
