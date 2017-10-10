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

namespace Pimcore\Tracking\Piwik;

use Pimcore\Config\Config;
use Pimcore\Event\Tracking\Piwik\CodeSnippetEvent;
use Pimcore\Event\Tracking\Piwik\TrackingDataEvent;
use Pimcore\Event\Tracking\PiwikTrackingCodeEvents;
use Pimcore\Http\Request\Resolver\SiteResolver;
use Pimcore\Model\Site;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Templating\EngineInterface;

class Tracker
{
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var EngineInterface
     */
    private $templatingEngine;

    /**
     * @var SiteResolver
     */
    private $siteResolver;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        EngineInterface $templatingEngine,
        SiteResolver $siteResolver
    )
    {
        $this->eventDispatcher  = $eventDispatcher;
        $this->templatingEngine = $templatingEngine;
        $this->siteResolver     = $siteResolver;
    }

    public function getCode(Site $site = null)
    {
        if (null !== $site) {
            return $this->getSiteCode($site);
        } elseif ($this->siteResolver->isSiteRequest()) {
            $site = $this->siteResolver->getSite();

            return $this->getSiteCode($site);
        }

        return $this->getMainCode();
    }

    public function getMainCode()
    {
        return $this->generateCode('default');
    }

    public function getSiteCode(Site $site)
    {
        $siteKey = sprintf('site_%s', $site->getId());

        return $this->generateCode($siteKey, $site);
    }

    private function generateCode(string $configKey = 'default', Site $site = null)
    {
        $reportConfig = \Pimcore\Config::getReportConfig();
        if (!$reportConfig ->piwik) {
            return null;
        }

        $config     = $reportConfig->piwik;
        $siteConfig = $config->sites->$configKey;

        if (!$siteConfig) {
            return null;
        }

        if (empty($config->piwik_url)) {
            return null;
        }

        if (empty($siteConfig->site_id)) {
            return null;
        }

        $data = $this->buildTemplateData($config, $siteConfig, $site);
        $code = $this->templatingEngine->render(
            '@PimcoreCore/Tracking/Piwik/trackingCode.html.twig',
            $data
        );

        $code = trim($code);

        return $code;
    }

    private function buildTemplateData(Config $config, Config $siteConfig, Site $site = null): array
    {
        $data = [
            'piwikUrl'   => $config->piwik_url,
            'siteId'     => $siteConfig->site_id,
            'beforeInit' => $this->generateCodeSnippet(
                PiwikTrackingCodeEvents::BEFORE_INIT,
                new CodeSnippetEvent([], $site)
            ),
            'track'      => $this->generateCodeSnippet(
                PiwikTrackingCodeEvents::TRACK,
                new CodeSnippetEvent($this->generateTrackingCalls(), $site)
            ),
            'asyncInit'  => $this->generateCodeSnippet(
                PiwikTrackingCodeEvents::ASYNC_INIT,
                new CodeSnippetEvent($this->generateAsyncInitCalls($siteConfig->site_id), $site)
            ),
            'afterAsync' => $this->generateCodeSnippet(
                PiwikTrackingCodeEvents::AFTER_ASYNC,
                new CodeSnippetEvent([], $site)
            ),
        ];

        $trackingDataEvent = new TrackingDataEvent($data, $config, $siteConfig, $site);
        $this->eventDispatcher->dispatch(PiwikTrackingCodeEvents::TRACKING_DATA, $trackingDataEvent);

        return $trackingDataEvent->getData();
    }

    public function addAdditionalCode(string $eventName, string $code)
    {
        $this->eventDispatcher->addListener($eventName, function (CodeSnippetEvent $event) use ($code) {
            $event->addPart($code);
        });
    }

    private function generateTrackingCalls(): array
    {
        return [
            "_paq.push(['trackPageView']);",
            "_paq.push(['enableLinkTracking']);",
        ];
    }

    private function generateAsyncInitCalls(int $siteId): array
    {
        return [
            "_paq.push(['setTrackerUrl', u+'piwik.php']);",
            sprintf("_paq.push(['setSiteId', '%d']);", $siteId)
        ];
    }

    private function generateCodeSnippet(string $eventName, CodeSnippetEvent $event): string
    {
        $this->eventDispatcher->dispatch($eventName, $event);

        $result = trim(implode("\n", $event->getParts()));

        return $result;
    }
}
