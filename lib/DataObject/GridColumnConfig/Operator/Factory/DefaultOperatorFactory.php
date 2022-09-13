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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\DataObject\GridColumnConfig\Operator\Factory;

use Pimcore\Logger;
use Symfony\Contracts\Translation\TranslatorInterface;

class DefaultOperatorFactory implements OperatorFactoryInterface
{
    /**
     * @var string
     */
    private $className;

    private TranslatorInterface $translator;

    /**
     * @param string $className
     */
    public function __construct(string $className, TranslatorInterface $translator)
    {
        $this->className = $className;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function build(\stdClass $configElement, array $context = [])
    {
        if (class_exists($this->className)) {
            return new $this->className($configElement, $context, $this->translator);
        }

        Logger::warn('operator ' . $this->className . ' does not exist');

        return null;
    }
}
