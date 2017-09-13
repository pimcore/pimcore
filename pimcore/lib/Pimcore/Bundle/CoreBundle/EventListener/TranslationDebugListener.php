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

namespace Pimcore\Bundle\CoreBundle\EventListener;

use Pimcore\Translation\Translator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class TranslationDebugListener implements EventSubscriberInterface
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var string
     */
    private $parameterName;

    public function __construct(
        Translator $translator,
        string $parameterName
    )
    {
        $this->translator    = $translator;
        $this->parameterName = $parameterName;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest'
        ];
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        if (empty($this->parameterName)) {
            return;
        }

        if ((bool)$event->getRequest()->query->get($this->parameterName, false)) {
            $this->translator->setDisableTranslations(true);
        }
    }
}
