<?php

declare(strict_types=1);

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

namespace Pimcore\DataObject\GridColumnConfig\Operator\Factory;

use Pimcore\DataObject\GridColumnConfig\Operator\OperatorInterface;
use Pimcore\DataObject\GridColumnConfig\Operator\TranslateValue;
use Symfony\Contracts\Translation\TranslatorInterface;

class TranslateValueFactory implements OperatorFactoryInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function build(\stdClass $configElement, $context = null): OperatorInterface
    {
        return new TranslateValue($this->translator, $configElement, $context);
    }
}
