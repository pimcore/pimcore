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

namespace Pimcore\Mail\Plugins;

use Pimcore\Helper\Mail as MailHelper;
use Pimcore\Mail;

class RedirectingPlugin extends \Swift_Plugins_RedirectingPlugin
{
    /**
     * Create a new RedirectingPlugin.
     *
     * @param mixed $recipient
     * @param array $whitelist
     */
    public function __construct($recipient, array $whitelist = [])
    {
        $explodedRecipientArray = [];
        foreach ($recipient as $r) {
            $explodedRecipientArray = array_merge($explodedRecipientArray, array_filter(explode(',', $r)));
        }

        parent::__construct($explodedRecipientArray, $whitelist);
    }

    /**
     * Invoked immediately before the Message is sent.
     *
     * @param \Swift_Events_SendEvent $evt
     */
    public function beforeSendPerformed(\Swift_Events_SendEvent $evt)
    {
        $message = $evt->getMessage();

        if ($message instanceof Mail) {
            // additional checks if message is Pimcore\Mail
            if ($message->doRedirectMailsToDebugMailAddresses()) {
                if (empty($this->getRecipient())) {
                    throw new \Exception('No valid debug email address given in "Settings" -> "System" -> "Email Settings"');
                }

                $this->appendDebugInformation($message);
                parent::beforeSendPerformed($evt);
            }
        } else {
            // default symfony behavior - only redirect when recipients are set and pimcore debug mode is active
            if (\Pimcore::inDebugMode() && $this->getRecipient()) {
                parent::beforeSendPerformed($evt);
            }
        }

        $headers = $message->getHeaders();
        if (\Pimcore::inDebugMode()) {
            $headers->addMailboxHeader('X-Pimcore-Debug-To', $message->getTo());
            $headers->addMailboxHeader('X-Pimcore-Debug-Cc', $message->getCc());
            $headers->addMailboxHeader('X-Pimcore-Debug-Bcc', $message->getBcc());
            $headers->addMailboxHeader('X-Pimcore-Debug-ReplyTo', $message->getReplyTo());
        }
    }

    /**
     * Invoked immediately after the Message is sent.
     *
     * @param \Swift_Events_SendEvent $evt
     */
    public function sendPerformed(\Swift_Events_SendEvent $evt)
    {
        parent::sendPerformed($evt);

        $message = $evt->getMessage();
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
    protected function appendDebugInformation(Mail $message)
    {
        if ($message->isPreventingDebugInformationAppending() != true) {
            $originalData = [];

            //adding the debug information to the html email
            $html = $message->getBody();
            if (!empty($html)) {
                $originalData['html'] = $html;

                $debugInformation = MailHelper::getDebugInformation('html', $message);
                $debugInformationStyling = MailHelper::getDebugInformationCssStyle();

                $html = preg_replace("!(</\s*body\s*>)!is", "$debugInformation\\1", $html);
                $html = preg_replace("!(<\s*head\s*>)!is", "\\1$debugInformationStyling", $html);

                $message->setBody($html, 'text/html');
            }

            $text = $message->getBodyTextMimePart();

            if (!empty($text)) {
                $originalData['text'] = $text->getBody();

                $rawText = $text->getBody();
                $debugInformation = MailHelper::getDebugInformation('text', $message);
                $rawText .= $debugInformation;
                $text->setBody($rawText);
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
    protected function setSenderAndReceiversParams($message)
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
    protected function removeDebugInformation(Mail $message)
    {
        $originalData = $message->getOriginalData();

        if (isset($originalData['html']) && $originalData['html']) {
            $message->setBody($originalData['html'], 'text/html');
        }
        if (isset($originalData['text']) && $originalData['text']) {
            $message->getBodyTextMimePart()->setBody($originalData['html']);
        }
        if (isset($originalData['subject']) && $originalData['subject']) {
            $message->setSubject($originalData['subject']);
        }

        $message->setOriginalData(null);
    }
}
