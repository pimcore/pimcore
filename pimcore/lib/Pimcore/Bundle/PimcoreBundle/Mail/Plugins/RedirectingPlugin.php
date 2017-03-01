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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\PimcoreBundle\Mail\Plugins;

use Pimcore\Helper\Mail as MailHelper;
use Pimcore\Mail;

class RedirectingPlugin extends \Swift_Plugins_RedirectingPlugin {


    /**
     * Create a new RedirectingPlugin.
     *
     * @param mixed $recipient
     * @param array $whitelist
     */
    public function __construct($recipient, array $whitelist = array())
    {
        $explodedRecipientArray = [];
        foreach($recipient as $r) {
            $explodedRecipientArray = array_merge($explodedRecipientArray, array_filter(explode(",", $r)));
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

        if($message instanceof Mail) {

            // additional checks if message is Pimcore\Mail
            if($message->doRedirectMailsToDebugMailAddresses()) {

                if(empty($this->getRecipient())) {
                    throw new \Exception('No valid debug email address given in "Settings" -> "System" -> "Email Settings"');
                }

                $this->appendDebugInformation($message);
                parent::beforeSendPerformed($evt);

            }

        } else {

            // default symfony behavior - only redirect when recipients are set and pimcore debug mode is active
            if(\Pimcore::inDebugMode() && $this->getRecipient()) {
                parent::beforeSendPerformed($evt);
            }

        }

    }

    /**
     * Invoked immediately after the Message is sent.
     *
     * @param \Swift_Events_SendEvent $evt
     */
    public function sendPerformed(\Swift_Events_SendEvent $evt) {
        parent::sendPerformed($evt);

        $message = $evt->getMessage();
        if($message instanceof Mail && $message->doRedirectMailsToDebugMailAddresses()) {
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

            $message->setOriginalData($originalData);
        }
    }

    /**
     * removes debug information from message and resets it
     *
     * @param Mail $message
     */
    protected function removeDebugInformation(Mail $message) {

        $originalData = $message->getOriginalData();

        if($originalData['html']) {
            $message->setBody($originalData['html'], 'text/html');
        }
        if($originalData['text']) {
            $message->getBodyTextMimePart()->setBody($originalData['html']);
        }
        if($originalData['subject']) {
            $message->setSubject($originalData['subject']);
        }

        $message->setOriginalData(null);
    }

}