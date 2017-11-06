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

namespace Pimcore\Targeting\DataProvider;

use Pimcore\Analytics\Piwik\Api\VisitorClient;
use Pimcore\Analytics\Piwik\Config\Config;
use Pimcore\Analytics\SiteId\SiteIdProvider;
use Pimcore\Targeting\Model\VisitorInfo;
use Pimcore\Targeting\Session\SessionConfigurator;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class Session implements DataProviderInterface
{
    const PROVIDER_KEY = 'session';

    /**
     * @var SessionInterface
     */
    private $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        return self::PROVIDER_KEY;
    }

    /**
     * @inheritDoc
     */
    public function load(VisitorInfo $visitorInfo)
    {
        if ($visitorInfo->has($this->getKey())) {
            return;
        }

        $visitorInfo->set(
            $this->getKey(),
            $this->loadData($visitorInfo)
        );
    }

    private function loadData(VisitorInfo $visitorInfo)
    {
        $request = $visitorInfo->getRequest();
        if (!$request->hasPreviousSession()) {
            return null;
        }

        $session = $request->getSession();

        return $session->getBag(SessionConfigurator::TARGETING_BAG);
    }
}
