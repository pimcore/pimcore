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

namespace Pimcore\Bundle\CoreBundle\EventListener\Frontend;

use Pimcore\Event\DocumentEvents;
use Pimcore\Templating\Helper\Placeholder\ContainerService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles block state for sub requests (saves parent state and restores it after request completes)
 */
class DocumentRendererListener implements EventSubscriberInterface
{
    /**
     * @var ContainerService
     */
    protected $containerService;

    public function __construct(ContainerService $containerService)
    {
        $this->containerService = $containerService;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            DocumentEvents::RENDERER_PRE_RENDER => 'onPreRender',
            DocumentEvents::RENDERER_POST_RENDER => 'onPostRender',
        ];
    }

    public function onPreRender()
    {
        // when rendering a new document, the index is pushed to create a new, empty context
        $this->containerService->pushIndex();
    }

    public function onPostRender()
    {
        $this->containerService->popIndex();
    }
}
