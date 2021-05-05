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

namespace Pimcore\Mail\Plugins;

use Pimcore\Helper\Mail as MailHelper;
use Pimcore\Mail;
use Symfony\Component\Mime\Header\MailboxListHeader;

/**
 * @internal
 */
final class RedirectingPlugin
{
    /**
     * The recipient who will receive all messages.
     *
     * @var array
     */
    private $recipient;

    /**
     * Create a new RedirectingPlugin.
     *
     * @param array $recipient
     */
    public function __construct(array $recipient = [])
    {
        $config = \Pimcore\Config::getSystemConfiguration('email');
        if (!empty($config['debug']['email_addresses'])) {
            $recipient = array_merge($recipient, array_filter(explode(',', $config['debug']['email_addresses'])));
        }

        $this->recipient = $recipient;
    }

    /**
     * Set the recipient of all messages.
     *
     * @param mixed $recipient
     */
    public function setRecipient($recipient)
    {
        $this->recipient = $recipient;
    }

    /**
     * Get the recipient of all messages.
     *
     * @return mixed
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

    /**
     * Invoked immediately before the Message is sent.
     *
     * @param Mail $message
     *
     */
    public function beforeSendPerformed(Mail $message)
    {
        // additional checks if message is Pimcore\Mail
        if ($message->doRedirectMailsToDebugMailAddresses()) {
            if (empty($this->getRecipient())) {
                throw new \Exception('No valid debug email address given in "Settings" -> "System" -> "Email Settings"');
            }

            $this->appendDebugInformation($message);
            // Add each hard coded recipient
            $to = $message->getTo();

            foreach ((array) $this->recipient as $recipient) {
                if (!array_key_exists($recipient, $to)) {
                    $message->to($recipient);
                }
            }
        }

        $headers = $message->getHeaders();
        if (\Pimcore::inDebugMode() && !$message->getIgnoreDebugMode()) {
            $headers->add(new MailboxListHeader('X-Pimcore-Debug-To', $message->getTo()));
            $headers->add(new MailboxListHeader('X-Pimcore-Debug-Cc', $message->getCc()));
            $headers->add(new MailboxListHeader('X-Pimcore-Debug-Bcc', $message->getBcc()));
            $headers->add(new MailboxListHeader('X-Pimcore-Debug-ReplyTo', $message->getReplyTo()));
        }
    }

    /**
     * Invoked immediately after the Message is sent.
     *
     * @param Mail $message
     */
    public function sendPerformed(Mail $message)
    {
        if ($message instanceof Mail && $message->doRedirectMailsToDebugMailAddresses()) {
            $this->setSenderAndReceiversParams($message);
            $this->removeDebugInformation($message);
        }
    }

    /**
     * Appends debug information to message
     *
     * @param Mail $message
     */
    private function appendDebugInformation(Mail $message)
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
            $message->setSubject('Debug email: ' . $subject);

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
     *
     * @param Mail $message
     */
    private function setSenderAndReceiversParams($message)
    {
        $originalData = $message->getOriginalData();

        $message->setParam('Debug-Redirected', 'true');
        foreach (['From', 'To', 'Cc', 'Bcc', 'ReplyTo'] as $k) {
            // Add parameters to show this was redirected
            $message->setParam('Debug-Original-' . $k, $originalData[$k]);
        }
    }

    /**
     * removes debug information from message and resets it
     *
     * @param Mail $message
     */
    private function removeDebugInformation(Mail $message)
    {
        $originalData = $message->getOriginalData();

        if (isset($originalData['html']) && $originalData['html']) {
            $message->html($originalData['html']);
        }
        if (isset($originalData['text']) && $originalData['text']) {
            $message->text($originalData['text']);
        }
        if (isset($originalData['subject']) && $originalData['subject']) {
            $message->setSubject($originalData['subject']);
        }

        $message->setOriginalData(null);
    }
}
