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

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Command\Voucher;

use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Factory;
use Pimcore\Console\AbstractCommand;

abstract class AbstractVoucherCommand extends AbstractCommand
{
    protected function cleanupReservations()
    {
        $this->output->writeln('<comment>*</comment> Cleaning up <info>reservations</info>');
        Factory::getInstance()->getVoucherService()->cleanUpReservations();
    }

    protected function cleanupStatistics()
    {
        $this->output->writeln('<comment>*</comment> Cleaning up <info>statistics</info>');
        Factory::getInstance()->getVoucherService()->cleanUpStatistics();
    }
}
