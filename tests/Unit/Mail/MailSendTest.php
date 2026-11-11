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

use InvalidArgumentException;
use Pimcore\Mail;
use Pimcore\Mail\Mailer;
use Pimcore\Mail\Plugins\RedirectingPlugin;
use Pimcore\Tests\Support\Test\TestCase;
use ReflectionClass;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;

/**
 * Sending checks the recipients against the (database based) blocklist, so these tests need a database
 */
class MailSendTest extends TestCase
{
    private const PAYLOAD = '{{ app.environment }}';

    protected bool $cleanupDbInSetup = false;

    protected function needsDb(): bool
    {
        return true;
    }

    /**
     * Test: params are inserted into the bodies, never interpreted as a template (neither in the html nor in the generated text part)
     */
    public function testSendDoesNotEvaluateParamsInBodies(): void
    {
        $mail = $this->createSendableMail();
        $mail->html('<html><body><p>Note: {{ note }}</p></body></html>');
        $mail->setParams(['note' => self::PAYLOAD]);
        $mail->send($this->createStub(MailerInterface::class));

        $this->assertStringContainsString(self::PAYLOAD, (string) $mail->getHtmlBody());
        $this->assertStringContainsString(self::PAYLOAD, (string) $mail->getTextBody());
    }

    /**
     * Test: a manually set text body is still rendered once with the params
     */
    public function testSendRendersManualTextBodyOnce(): void
    {
        $mail = $this->createSendableMail();
        $mail->html('<p>Hi {{ name }}</p>');
        $mail->text('Hello {{ name }}');
        $mail->setParams(['name' => self::PAYLOAD]);
        $mail->send($this->createStub(MailerInterface::class));

        $this->assertSame('Hello ' . self::PAYLOAD, $mail->getTextBody());
        $this->assertStringContainsString('Hi ' . self::PAYLOAD, (string) $mail->getHtmlBody());
    }

    /**
     * Test: sending the same mail object again must not render the already rendered content a second time
     */
    public function testSendingTheSameMailTwiceDoesNotEvaluateParams(): void
    {
        $mail = $this->createSendableMail();
        $mail->html('<html><body><p>Note: {{ note }}</p></body></html>');
        $mail->setParams(['note' => self::PAYLOAD]);
        $mailer = $this->createStub(MailerInterface::class);
        $mail->send($mailer);
        $mail->send($mailer);

        $this->assertStringContainsString(self::PAYLOAD, (string) $mail->getHtmlBody());
        $this->assertStringContainsString(self::PAYLOAD, (string) $mail->getTextBody());
    }

    /**
     * Test: the rendered subject is final and not rendered again (e.g. when the mail is logged)
     */
    public function testRenderedSubjectIsNotRenderedAgain(): void
    {
        $mail = $this->createSendableMail();
        $mail->subject('Contact {{ name }}');
        $mail->html('<p>{{ name }}</p>');
        $mail->setParams(['name' => self::PAYLOAD]);
        $mail->send($this->createStub(MailerInterface::class));

        $this->assertSame('Contact ' . self::PAYLOAD, $mail->getSubject());
        $this->assertSame('Contact ' . self::PAYLOAD, $mail->getSubjectRendered());
    }

    /**
     * Test: a mail that was serialized after sending (e.g. for the Symfony Messenger) keeps its rendered state
     */
    public function testRenderedStateSurvivesSerialization(): void
    {
        $mail = $this->createSendableMail();
        $mail->subject('Contact {{ name }}');
        $mail->html('<html><body><p>Note: {{ name }}</p></body></html>');
        $mail->setParams(['name' => self::PAYLOAD]);
        $mail->send($this->createStub(MailerInterface::class));

        $restored = unserialize(serialize($mail));
        $this->assertInstanceOf(Mail::class, $restored);
        $restored->disableLogging();

        $this->assertSame('Contact ' . self::PAYLOAD, $restored->getSubjectRendered());

        $restored->send($this->createStub(MailerInterface::class));
        $this->assertSame('Contact ' . self::PAYLOAD, $restored->getSubject());
        $this->assertStringContainsString(self::PAYLOAD, (string) $restored->getHtmlBody());
        $this->assertStringContainsString(self::PAYLOAD, (string) $restored->getTextBody());
    }

    /**
     * Test: when sending in debug mode fails, the debug decorations are removed again, so the subject
     * that is logged afterwards is still recognized as already rendered
     */
    public function testFailedSendInDebugModeRestoresRenderedContent(): void
    {
        $mail = $this->createRenderedMail();
        $renderedSubject = $mail->getSubject();

        $this->expectException(TransportException::class);

        try {
            $this->sendInDebugMode($mail, ['debug@example.com'], new TransportException('transport failed'));
        } finally {
            $this->assertSame($renderedSubject, $mail->getSubject());
            $this->assertSame($renderedSubject, $mail->getSubjectRendered());
        }
    }

    /**
     * Test: an invalid debug recipient fails after the subject was already decorated, which is restored as well
     */
    public function testInvalidDebugRecipientRestoresRenderedContent(): void
    {
        $mail = $this->createRenderedMail();
        $renderedSubject = $mail->getSubject();

        $this->expectException(InvalidArgumentException::class);

        try {
            $this->sendInDebugMode($mail, ['not a mail address'], new TransportException('must not be reached'));
        } finally {
            $this->assertSame($renderedSubject, $mail->getSubject());
            $this->assertSame($renderedSubject, $mail->getSubjectRendered());
        }
    }

    /**
     * Test: without debug information there is nothing to restore, the transport exception is not masked
     */
    public function testFailedSendInDebugModeWithoutDebugInformationThrowsTransportException(): void
    {
        $mail = $this->createRenderedMail();
        $mail->preventDebugInformationAppending();

        $this->expectException(TransportException::class);

        $this->sendInDebugMode($mail, ['debug@example.com'], new TransportException('transport failed'));
    }

    private function createRenderedMail(): Mail
    {
        $mail = $this->createSendableMail();
        $mail->subject('Contact {{ name }}');
        $mail->html('<html><body><p>Note: {{ name }}</p></body></html>');
        $mail->setParams(['name' => self::PAYLOAD]);
        $mail->send($this->createStub(MailerInterface::class));

        return $mail;
    }

    /**
     * @param string[] $debugRecipients
     */
    private function sendInDebugMode(Mail $mail, array $debugRecipients, TransportException $transportFailure): void
    {
        $failingMailer = $this->createStub(MailerInterface::class);
        $failingMailer->method('send')->willThrowException($transportFailure);

        // the constructor would read the debug addresses from the system settings, which are not set up in the test environment
        $plugin = (new ReflectionClass(RedirectingPlugin::class))->newInstanceWithoutConstructor();
        $plugin->setRecipient($debugRecipients);

        Mail::setForceDebugMode(true);

        try {
            (new Mailer($failingMailer, $plugin))->send($mail);
        } finally {
            Mail::setForceDebugMode(false);
        }
    }

    private function createSendableMail(): Mail
    {
        $mail = new Mail();
        $mail->disableLogging();
        $mail->from('jane@doe.com');
        $mail->to('john@doe.com');
        $mail->subject('Test Subject');

        return $mail;
    }
}
