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

namespace Pimcore\Mail;

use Pimcore\Mail;
use Pimcore\Mail\Plugins\RedirectingPlugin;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\RawMessage;

class Mailer implements MailerInterface
{
    protected MailerInterface $mailer;

    protected RedirectingPlugin $redirectPlugin;

    public function __construct(MailerInterface $mailer, RedirectingPlugin $redirectPlugin)
    {
        $this->mailer = $mailer;
        $this->redirectPlugin = $redirectPlugin;
    }

    public function send(RawMessage $message, Envelope $envelope = null): void
    {
        if ($message instanceof Mail) {
            $this->redirectPlugin->beforeSendPerformed($message);
        }

        if ($message instanceof Message && !$message->getHeaders()->has('X-Transport')) {
            $message->getHeaders()->addTextHeader('X-Transport', 'main');
        }

        $this->mailer->send($message, $envelope);

        if ($message instanceof Mail) {
            $this->redirectPlugin->sendPerformed($message);
        }
    }
}
