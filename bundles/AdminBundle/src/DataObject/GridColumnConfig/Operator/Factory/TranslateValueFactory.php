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

namespace Pimcore\Bundle\AdminBundle\DataObject\GridColumnConfig\Operator\Factory;

use Pimcore\Bundle\AdminBundle\DataObject\GridColumnConfig\Operator\OperatorInterface;
use Pimcore\Bundle\AdminBundle\DataObject\GridColumnConfig\Operator\TranslateValue;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
final class TranslateValueFactory implements OperatorFactoryInterface
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function build(\stdClass $configElement, array $context = []): OperatorInterface
    {
        return new TranslateValue($this->translator, $configElement, $context);
    }
}
