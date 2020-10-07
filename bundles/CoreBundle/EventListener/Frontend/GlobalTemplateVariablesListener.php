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

use Pimcore\Bundle\AdminBundle\Security\User\UserLoader;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Http\RequestHelper;
use Pimcore\Model\DataObject\Service;
use Pimcore\Model\Document;
use Pimcore\Model\Staticroute;
use Pimcore\Model\Version;
use Pimcore\Targeting\Document\DocumentTargetingConfigurator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

class GlobalTemplateVariablesListener implements EventSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    use PimcoreContextAwareTrait;

    /**
     * @var DocumentResolver
     */
    protected $documentResolver;

    /**
     * @var EditmodeResolver
     */
    protected $editmodeResolver;

    /**
     * @var Environment
     */
    protected $twig;

    public function __construct(
        DocumentResolver $documentResolver,
        EditmodeResolver $editmodeResolver,
        Environment $twig
    ) {
        $this->documentResolver = $documentResolver;
        $this->editmodeResolver = $editmodeResolver;
        $this->twig = $twig;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController', 90], // has to be after DocumentFallbackListener
        ];
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        $request = $event->getRequest();
        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        $document = $this->documentResolver->getDocument($request);
        $editmode = $this->editmodeResolver->isEditmode($request);

        $this->twig->addGlobal('document', $document);
        $this->twig->addGlobal('editmode', $editmode);
    }
}
