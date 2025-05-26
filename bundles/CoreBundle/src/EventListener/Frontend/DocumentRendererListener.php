<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\CoreBundle\EventListener\Frontend;

use Pimcore\Event\DocumentEvents;
use Pimcore\Twig\Extension\Templating\Placeholder\ContainerService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles block state for sub requests (saves parent state and restores it after request completes)
 *
 * @internal
 */
class DocumentRendererListener implements EventSubscriberInterface
{
    public function __construct(protected ContainerService $containerService)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DocumentEvents::RENDERER_PRE_RENDER => 'onPreRender',
            DocumentEvents::RENDERER_POST_RENDER => 'onPostRender',
        ];
    }

    public function onPreRender(): void
    {
        // when rendering a new document, the index is pushed to create a new, empty context
        $this->containerService->pushIndex();
    }

    public function onPostRender(): void
    {
        $this->containerService->popIndex();
    }
}
