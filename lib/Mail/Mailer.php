<?php

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Mail;

use Pimcore\Mail;
use Pimcore\Mail\Plugins\RedirectingPlugin;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\RawMessage;

class Mailer implements MailerInterface
{
    /**
     * @var MailerInterface
     */
    protected $mailer;

    /**
     * @var RedirectingPlugin
     */
    protected RedirectingPlugin $redirectPlugin;

    /**
     * @param MailerInterface $mailer
     */
    public function __construct(MailerInterface $mailer, RedirectingPlugin $redirectPlugin)
    {
        $this->mailer = $mailer;
        $this->redirectPlugin = $redirectPlugin;
    }

    /**
     * {@inheritdoc}
     */
    public function send(RawMessage $message, Envelope $envelope = null): void
    {
        if ($message instanceof Mail) {
            $this->redirectPlugin->beforeSendPerformed($message);
        }

        $this->mailer->send($message, $envelope);

        if ($message instanceof Mail) {
            $this->redirectPlugin->sendPerformed($message);
        }
    }
}
