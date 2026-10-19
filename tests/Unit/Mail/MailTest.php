<?php

declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Tests\Unit\Mail;

use Exception;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Part\TextPart;

class MailTest extends TestCase
{
    private array $defaultSettings = [
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
    public function testGenerateMail(): void
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
    public function testGenerateMailWithArray(): void
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
    public function testMailInit(): void
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
    public function testAddRecipientsToMail(): void
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
    public function testClearRecipientsFromMail(): void
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
    public function testTextBodyRenderedWithParams(): void
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
    public function testHtmlBodyRenderedWithParams(): void
    {
        $mail = new \Pimcore\Mail();
        $mail->html('Hi, {{ firstname }} {{ lastname }}.');
        $mail->setParams([
            'firstname' => 'John',
            'lastname' => 'Doe',
        ]);

        $this->assertStringContainsString('Hi, John Doe.', $mail->getBodyHtmlRendered());
    }

    /**
     * Test: params are inserted into the bodies, never interpreted as a template (neither in the html nor in the generated text part)
     */
    public function testSendDoesNotEvaluateParamsInBodies(): void
    {
        $payload = '{{ app.environment }}';

        $mail = $this->createSendableMail();
        $mail->html('<html><body><p>Note: {{ note }}</p></body></html>');
        $mail->setParams(['note' => $payload]);
        $mail->send($this->createStub(MailerInterface::class));

        $this->assertStringContainsString($payload, (string) $mail->getHtmlBody());
        $this->assertStringContainsString($payload, (string) $mail->getTextBody());
    }

    /**
     * Test: a manually set text body is still rendered once with the params
     */
    public function testSendRendersManualTextBodyOnce(): void
    {
        $payload = '{{ app.environment }}';

        $mail = $this->createSendableMail();
        $mail->html('<p>Hi {{ name }}</p>');
        $mail->text('Hello {{ name }}');
        $mail->setParams(['name' => $payload]);
        $mail->send($this->createStub(MailerInterface::class));

        $this->assertSame('Hello ' . $payload, $mail->getTextBody());
        $this->assertStringContainsString('Hi ' . $payload, (string) $mail->getHtmlBody());
    }

    /**
     * Test: sending the same mail object again must not render the already rendered content a second time
     */
    public function testSendingTheSameMailTwiceDoesNotEvaluateParams(): void
    {
        $payload = '{{ app.environment }}';

        $mail = $this->createSendableMail();
        $mail->html('<html><body><p>Note: {{ note }}</p></body></html>');
        $mail->setParams(['note' => $payload]);
        $mailer = $this->createStub(MailerInterface::class);
        $mail->send($mailer);
        $mail->send($mailer);

        $this->assertStringContainsString($payload, (string) $mail->getHtmlBody());
        $this->assertStringContainsString($payload, (string) $mail->getTextBody());
    }

    /**
     * Test: the rendered subject is final and not rendered again (e.g. when the mail is logged)
     */
    public function testRenderedSubjectIsNotRenderedAgain(): void
    {
        $payload = '{{ app.environment }}';

        $mail = $this->createSendableMail();
        $mail->subject('Contact {{ name }}');
        $mail->html('<p>{{ name }}</p>');
        $mail->setParams(['name' => $payload]);
        $mail->send($this->createStub(MailerInterface::class));

        $this->assertSame('Contact ' . $payload, $mail->getSubject());
        $this->assertSame('Contact ' . $payload, $mail->getSubjectRendered());
    }

    /**
     * Test: a template expression blocked by the sandbox results in a handled exception, not in a TypeError
     */
    public function testSandboxViolationThrowsHandledException(): void
    {
        $mail = new \Pimcore\Mail();
        $mail->html("<p>{{ 'a'|upper }}</p>");

        try {
            $mail->getBodyHtmlRendered();
            $this->fail('Rendering a blocked filter must fail');
        } catch (Exception $e) {
            $this->assertStringContainsString('Failed rendering the body', $e->getMessage());
            $this->assertStringContainsString('upper', $e->getMessage());
        }
    }

    private function createSendableMail(): \Pimcore\Mail
    {
        $mail = new \Pimcore\Mail();
        $mail->disableLogging();
        $mail->from($this->defaultSettings['from']);
        $mail->to($this->defaultSettings['to']);
        $mail->subject($this->defaultSettings['subject']);

        return $mail;
    }
}
