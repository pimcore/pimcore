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

namespace Pimcore\Tests\Test;

use Pimcore\Bundle\EcommerceFrameworkBundle\Environment;
use Pimcore\Bundle\EcommerceFrameworkBundle\EnvironmentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tools\SessionConfigurator;
use Pimcore\Localization\LocaleService;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

abstract class EcommerceTestCase extends TestCase
{
    /**
     * @var EnvironmentInterface
     */
    private $environment;

    /**
     * @deprecated will be removed in Pimcore 11
     *
     * @var SessionInterface
     */
    private $session;

    protected function buildEnvironment(): EnvironmentInterface
    {
        if (null === $this->environment) {
            $this->environment = new Environment(new LocaleService());
        }

        return $this->environment;
    }

    protected function buildSession(): SessionInterface
    {
        $session = \Pimcore::getContainer()->get('request_stack')->getSession();

        if (null === $session) {
            $session = new Session(new MockArraySessionStorage());

            $configurator = new SessionConfigurator();
            $configurator->configure($this->session);

            $session->getBag(SessionConfigurator::ATTRIBUTE_BAG_CART)->set('carts', []);
        }

        $this->session = $session;

        return $session;
    }
}
