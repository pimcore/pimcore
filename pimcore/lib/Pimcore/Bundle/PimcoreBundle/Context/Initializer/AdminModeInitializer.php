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

namespace Pimcore\Bundle\PimcoreBundle\Context\Initializer;

use Pimcore\Bundle\PimcoreBundle\Service\Request\PimcoreContextResolver;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

class AdminModeInitializer extends AbstractContextInitializer implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @inheritDoc
     */
    public function supports($context)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function initialize(Request $request, $context, $requestType = KernelInterface::MASTER_REQUEST)
    {
        if (!$this->isMasterRequest($requestType)) {
            return;
        }

        if ($context === PimcoreContextResolver::CONTEXT_ADMIN) {
            $this->logger->debug('Setting admin mode');
            \Pimcore::setAdminMode();
        } else {
            $this->logger->debug('Unsetting admin mode');
            \Pimcore::unsetAdminMode();
        }
    }
}
