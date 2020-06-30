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

namespace Pimcore\Targeting\Code;

use Pimcore\Analytics\Code\CodeBlock;
use Pimcore\Event\Targeting\TargetingCodeEvent;
use Pimcore\Event\TargetingEvents;
use Pimcore\Targeting\Model\VisitorInfo;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Templating\EngineInterface;

class TargetingCodeGenerator
{
    const BLOCK_BEFORE_SCRIPT_TAG = 'beforeScriptTag';
    const BLOCK_BEFORE_SCRIPT = 'beforeScript';
    const BLOCK_AFTER_SCRIPT = 'afterScript';
    const BLOCK_AFTER_SCRIPT_TAG = 'afterScriptTag';

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
    private $blocks = [
        self::BLOCK_BEFORE_SCRIPT_TAG,
        self::BLOCK_BEFORE_SCRIPT,
        self::BLOCK_AFTER_SCRIPT,
        self::BLOCK_AFTER_SCRIPT_TAG,
    ];

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        EngineInterface $templatingEngine
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->templatingEngine = $templatingEngine;
    }

    public function generateCode(VisitorInfo $visitorInfo): string
    {
        $data = [
            'inDebugMode' => \Pimcore::inDebugMode(),
            'dataProviderKeys' => $visitorInfo->getFrontendDataProviders(),
        ];

        $event = new TargetingCodeEvent(
            '@PimcoreCore/Targeting/targetingCode.html.twig',
            $this->buildCodeBlocks(),
            $data
        );

        $this->eventDispatcher->dispatch(TargetingEvents::TARGETING_CODE, $event);

        return $this->renderTemplate($event);
    }

    private function renderTemplate(TargetingCodeEvent $event): string
    {
        $data = $event->getData();
        $data['blocks'] = $event->getBlocks();

        $code = $this->templatingEngine->render(
            $event->getTemplate(),
            $data
        );

        $code = trim($code);

        return $code;
    }

    private function buildCodeBlocks(): array
    {
        $blocks = [];
        foreach ($this->blocks as $block) {
            $blocks[$block] = new CodeBlock();
        }

        return $blocks;
    }
}
