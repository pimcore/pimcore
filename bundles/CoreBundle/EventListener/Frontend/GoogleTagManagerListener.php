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

namespace Pimcore\Bundle\CoreBundle\EventListener\Frontend;

use Pimcore\Analytics\Code\CodeBlock;
use Pimcore\Analytics\SiteId\SiteIdProvider;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\EnabledTrait;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PreviewRequestTrait;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\ResponseInjectionTrait;
use Pimcore\Config;
use Pimcore\Event\Analytics\Google\TagManager\CodeEvent;
use Pimcore\Event\Analytics\GoogleTagManagerEvents;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Tool;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Templating\EngineInterface;

class GoogleTagManagerListener
{
    const BLOCK_HEAD_BEFORE_SCRIPT_TAG = 'beforeScriptTag';
    const BLOCK_HEAD_AFTER_SCRIPT_TAG = 'afterScriptTag';

    const BLOCK_BODY_BEFORE_NOSCRIPT_TAG = 'beforeNoscriptTag';
    const BLOCK_BODY_AFTER_NOSCRIPT_TAG = 'afterNoscriptTag';

    use EnabledTrait;
    use ResponseInjectionTrait;
    use PimcoreContextAwareTrait;
    use PreviewRequestTrait;

    /**
     * @var SiteIdProvider
     */
    private $siteIdProvider;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var EngineInterface
     */
    private $templatingEngine;

    /**
     * @var array
     */
    private $headBlocks = [
        self::BLOCK_HEAD_BEFORE_SCRIPT_TAG,
        self::BLOCK_HEAD_AFTER_SCRIPT_TAG,
    ];

    /**
     * @var array
     */
    private $bodyBlocks = [
        self::BLOCK_BODY_BEFORE_NOSCRIPT_TAG,
        self::BLOCK_BODY_AFTER_NOSCRIPT_TAG,
    ];

    public function __construct(
        SiteIdProvider $siteIdProvider,
        EventDispatcherInterface $eventDispatcher,
        EngineInterface $templatingEngine
    ) {
        $this->siteIdProvider = $siteIdProvider;
        $this->eventDispatcher = $eventDispatcher;
        $this->templatingEngine = $templatingEngine;
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$this->isEnabled()) {
            return;
        }

        $request = $event->getRequest();
        if (!$event->isMasterRequest()) {
            return;
        }

        // only inject tag manager code on non-admin requests
        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        if (!Tool::useFrontendOutputFilters()) {
            return;
        }

        if ($this->isPreviewRequest($request)) {
            return;
        }

        $siteId = $this->siteIdProvider->getForRequest($event->getRequest());
        $siteKey = $siteId->getConfigKey();

        $reportConfig = Config::getReportConfig();
        if (!isset($reportConfig->get('tagmanager')->sites->$siteKey->containerId)) {
            return;
        }

        $containerId = $reportConfig->get('tagmanager')->sites->$siteKey->containerId;
        if (!$containerId) {
            return;
        }

        $response = $event->getResponse();
        if (!$this->isHtmlResponse($response)) {
            return;
        }

        $codeHead = $this->generateCode(
            GoogleTagManagerEvents::CODE_HEAD,
            '@PimcoreCore/Google/TagManager/codeHead.html.twig',
            $this->headBlocks,
            [
                'containerId' => $containerId,
            ]
        );

        $codeBody = $this->generateCode(
            GoogleTagManagerEvents::CODE_BODY,
            '@PimcoreCore/Google/TagManager/codeBody.html.twig',
            $this->bodyBlocks,
            [
                'containerId' => $containerId,
            ]
        );

        $content = $response->getContent();

        if (!empty($codeHead)) {
            // search for the end <head> tag, and insert the google tag manager code before
            // this method is much faster than using simple_html_dom and uses less memory
            $headEndPosition = stripos($content, '</head>');
            if ($headEndPosition !== false) {
                $content = substr_replace($content, $codeHead . '</head>', $headEndPosition, 7);
            }
        }

        if (!empty($codeBody)) {
            // insert code after the opening <body> tag
            $content = preg_replace('@<body(>|.*?[^?]>)@', "<body$1\n\n" . $codeBody, $content);
        }

        $response->setContent($content);
    }

    private function generateCode(string $eventName, string $template, array $blockNames, array $data): string
    {
        $blocks = [];
        foreach ($blockNames as $blockName) {
            $blocks[$blockName] = new CodeBlock();
        }

        $event = new CodeEvent($data, $blocks, $template);

        $this->eventDispatcher->dispatch($eventName, $event);

        return $this->renderTemplate($event);
    }

    private function renderTemplate(CodeEvent $event): string
    {
        $data = $event->getData();
        $data['blocks'] = $event->getBlocks();

        $code = $this->templatingEngine->render(
            $event->getTemplate(),
            $data
        );

        $code = trim($code);
        if (!empty($code)) {
            $code = "\n" . $code . "\n";
        }

        return $code;
    }
}
