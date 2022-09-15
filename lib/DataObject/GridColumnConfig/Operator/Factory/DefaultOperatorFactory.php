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

use Pimcore\DataObject\GridColumnConfig\Operator\TranslatorAwareOperatorInterface;
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
     * @required
     */
    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

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
            $newClass = new $this->className($configElement, $context);
            if($newClass instanceof TranslatorAwareOperatorInterface){
                $newClass->setTranslator($this->translator);
            }
            return $newClass;
        }

        Logger::warn('operator ' . $this->className . ' does not exist');

        return null;
    }
}
