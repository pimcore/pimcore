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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Tests\Unit\Cache;

use Pimcore\Tests\Test\TestCase;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Part\TextPart;

class MailTest extends TestCase
{
    /**
     * @var array
     */
    private $defaultSettings = [
        'from' => 'jane@doe.com',
        'to' => 'john@doe.com',
        'cc' => 'john-cc@doe.com',
        'bcc' => 'john-bcc@doe.com',
        'replyto' => 'john-reply-to@doe.com',
        'subject' => 'Test Subject',
        'text' => 'This is a test mail.',
        'html' => 'This is a <b>test</b> mail.',
    ];

    /**
     * Test: Mail generate with header and body param
     */
    public function testGenerateMail()
    {
        $headers = (new Headers())
            ->addMailboxListHeader('From', [$this->defaultSettings['from']])
            ->addMailboxListHeader('To', [$this->defaultSettings['to']])
            ->addTextHeader('Subject', $this->defaultSettings['subject']);
        $body = new TextPart($this->defaultSettings['text']);

        $mail = new \Pimcore\Mail($headers, $body);

        $this->assertEquals($this->defaultSettings['from'], $mail->getFrom()[0]->getAddress(), 'From recipient is not set properly');
        $this->assertEquals($this->defaultSettings['to'], $mail->getTo()[0]->getAddress(), 'To recipient is not set properly');
        $this->assertEquals($this->defaultSettings['subject'], $mail->getSubject(), 'Subject body not set properly');
    }

    /**
     * Test: Mail generate with array param
     */
    public function testGenerateMailWithArray()
    {
        $headers = (new Headers())
            ->addMailboxListHeader('From', [$this->defaultSettings['from']])
            ->addMailboxListHeader('To', [$this->defaultSettings['to']]);

        $body = new TextPart($this->defaultSettings['text']);

        $mailArray = [
            'headers' => $headers,
            'body' => $body,
            'subject' => $this->defaultSettings['subject'],
        ];

        $mail = new \Pimcore\Mail($mailArray);

        $this->assertEquals($this->defaultSettings['from'], $mail->getFrom()[0]->getAddress(), 'From recipient is not set properly');
        $this->assertEquals($this->defaultSettings['to'], $mail->getTo()[0]->getAddress(), 'To recipient is not set properly');
        $this->assertEquals($this->defaultSettings['subject'], $mail->getSubject(), 'Subject body not set properly');
    }

    /**
     * Test: Initializes the mailer with the settings form Settings -> System -> Email Settings
     */
    public function testMailInit()
    {
        $emailConfig = \Pimcore\Config::getSystemConfiguration('email');

        $mail = new \Pimcore\Mail();

        $this->assertEquals($emailConfig['sender']['email'], $mail->getFrom()[0]->getAddress(), 'From recipient not initialized from system settings');
        if (!empty($emailConfig['return']['email'])) {
            $this->assertEquals($emailConfig['return']['email'], $mail->getReplyTo()[0]->getAddress(), 'replyTo recipient not initialized from system settings');
        }
    }

    /**
     * Test: Add Recipients to Mail with params email, name
     */
    public function testAddRecipientsToMail()
    {
        $mail = new \Pimcore\Mail();
        $mail->clearRecipients();
        foreach (['To', 'Cc', 'Bcc', 'ReplyTo'] as $key) {
            $setterName = 'add' . $key;
            $mail->$setterName($this->defaultSettings[strtolower($key)], 'John Doe');
        }

        foreach (['To', 'Cc', 'Bcc', 'ReplyTo'] as $key) {
            $getterName = 'get' . $key;
            $this->assertEquals($this->defaultSettings[strtolower($key)], $mail->$getterName()[0]->getAddress(), sprintf('%s Recipients not set.', $key));
        }
    }

    /**
     * Test: Clear Recipients from Mail
     */
    public function testClearRecipientsFromMail()
    {
        $mail = new \Pimcore\Mail();
        $mail->addTo($this->defaultSettings['to'])
            ->addCc($this->defaultSettings['to'])
            ->addBcc($this->defaultSettings['to'])
            ->addReplyTo($this->defaultSettings['to']);

        $mail->clearRecipients();

        foreach (['To', 'Cc', 'Bcc', 'ReplyTo'] as $key) {
            $getterName = 'get' . $key;
            $this->assertEmpty($mail->$getterName(), sprintf('%s Recipients not cleared.', $key));
        }
    }

    /**
     * Test: Text body render with Params
     */
    public function testTextBodyRenderedWithParams()
    {
        $mail = new \Pimcore\Mail();
        $mail->text('Hi, {{ firstname }} {{ lastname }}.');
        $mail->setParams([
            'firstname' => 'John',
            'lastname' => 'Doe',
        ]);

        $this->assertEquals('Hi, John Doe.', $mail->getBodyTextRendered());
    }

    /**
     * Test: Html body render with Params
     */
    public function testHtmlBodyRenderedWithParams()
    {
        $mail = new \Pimcore\Mail();
        $mail->html('Hi, {{ firstname }} {{ lastname }}.');
        $mail->setParams([
            'firstname' => 'John',
            'lastname' => 'Doe',
        ]);

        $this->assertStringContainsString('Hi, John Doe.', $mail->getBodyHtmlRendered());
    }
}
