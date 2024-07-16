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

namespace Pimcore\Mail\Plugins;

use Exception;
use Pimcore\Helper\Mail as MailHelper;
use Pimcore\Mail;
use Pimcore\SystemSettingsConfig;
use Symfony\Component\Mime\Header\MailboxListHeader;

/**
 * @internal
 */
final class RedirectingPlugin
{
    /**
     * The recipient who will receive all messages.
     *
     */
    private array $recipient;

    /**
     * Create a new RedirectingPlugin.
     *
     */
    public function __construct(array $recipient = [])
    {
        $config = SystemSettingsConfig::get()['email'];
        if (!empty($config['debug']['email_addresses'])) {
            $recipient = array_merge($recipient, array_filter(explode(',', $config['debug']['email_addresses'])));
        }

        $this->recipient = $recipient;
    }

    /**
     * Set the recipient of all messages.
     *
     */
    public function setRecipient(array $recipient): void
    {
        $this->recipient = $recipient;
    }

    /**
     * Get the recipient of all messages.
     *
     */
    public function getRecipient(): array
    {
        return $this->recipient;
    }

    /**
     * Invoked immediately before the Message is sent.
     *
     *
     */
    public function beforeSendPerformed(Mail $message): void
    {
        // additional checks if message is Pimcore\Mail
        if ($message->doRedirectMailsToDebugMailAddresses()) {
            if (empty($this->getRecipient())) {
                throw new Exception('No valid debug email address given in "Settings" -> "System Settings" -> "Debug"');
            }

            $this->appendDebugInformation($message);

            // Set headers first to get actual data
            $headers = $message->getHeaders();
            $headers->add(new MailboxListHeader('X-Pimcore-Debug-To', $message->getTo()));
            $headers->add(new MailboxListHeader('X-Pimcore-Debug-Cc', $message->getCc()));
            $headers->add(new MailboxListHeader('X-Pimcore-Debug-Bcc', $message->getBcc()));
            $headers->add(new MailboxListHeader('X-Pimcore-Debug-ReplyTo', $message->getReplyTo()));

            // Clear all recipients before setting debug recipients
            $message->clearRecipients();

            // Add debug recipients as recipients
            foreach ($this->recipient as $recipient) {
                $message->addTo($recipient);
            }
        }
    }

    /**
     * Invoked immediately after the Message is sent.
     */
    public function sendPerformed(Mail $message): void
    {
        if ($message instanceof Mail && $message->doRedirectMailsToDebugMailAddresses()) {
            $this->setSenderAndReceiversParams($message);
            $this->removeDebugInformation($message);
        }
    }

    /**
     * Appends debug information to message
     */
    private function appendDebugInformation(Mail $message): void
    {
        if ($message->isPreventingDebugInformationAppending() != true) {
            $originalData = [];

            //adding the debug information to the html email
            $html = $message->getHtmlBody();
            $text = $message->getTextBody();
            if (!empty($html)) {
                $originalData['html'] = $html;

                $debugInformation = MailHelper::getDebugInformation('html', $message);
                $debugInformationStyling = MailHelper::getDebugInformationCssStyle();

                $html = preg_replace("!(</\s*body\s*>)!is", "$debugInformation\\1", $html);
                $html = preg_replace("!(<\s*head\s*>)!is", "\\1$debugInformationStyling", $html);

                $message->html($html);
            } elseif (!empty($text)) {
                $originalData['text'] = $text;

                $rawText = $text;
                $debugInformation = MailHelper::getDebugInformation('text', $message);
                $rawText .= $debugInformation;
                $message->text($rawText);
            }

            //setting debug subject
            $subject = $message->getSubject();

            $originalData['subject'] = $subject;
            $message->subject('Debug email: ' . $subject);

            // Set receiver & sender data.
            $originalData['From'] = $message->getFrom();
            $originalData['To'] = $message->getTo();
            $originalData['Cc'] = $message->getCc();
            $originalData['Bcc'] = $message->getBcc();
            $originalData['ReplyTo'] = $message->getReplyTo();

            $message->setOriginalData($originalData);
        }
    }

    /**
     * Sets the sender and receiver information of the mail to keep the log searchable for the original data.
     */
    private function setSenderAndReceiversParams(Mail $message): void
    {
        $originalData = $message->getOriginalData();

        $message->setParam('Debug-Redirected', 'true');
        foreach (['From', 'To', 'Cc', 'Bcc', 'ReplyTo'] as $k) {
            // Add parameters to show this was redirected
            $message->setParam('Debug-Original-' . $k, MailHelper::formatDebugReceivers($originalData[$k]));
        }
    }

    /**
     * removes debug information from message and resets it
     */
    private function removeDebugInformation(Mail $message): void
    {
        $originalData = $message->getOriginalData();

        if (isset($originalData['html']) && $originalData['html']) {
            $message->html($originalData['html']);
        }
        if (isset($originalData['text']) && $originalData['text']) {
            $message->text($originalData['text']);
        }
        if (isset($originalData['subject']) && $originalData['subject']) {
            $message->subject($originalData['subject']);
        }

        $message->setOriginalData(null);
    }
}
