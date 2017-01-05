<?php

namespace TestSuite\Pimcore;

use PHPUnit\Framework\TestCase;
use Pimcore\Mail;

class MailTest extends TestCase
{
    public function testNormalFrom()
    {
        $email = 'foo@example.com';

        $mail = new Mail();
        $mail->setFrom($email, 'Sender\'s name');

        $this->assertEquals($email, $mail->getFrom());
    }

    /**
     * See issue #1165 ("pwnscriptum")
     *
     * @expectedException \RuntimeException
     */
    public function testMailFromInjectionMitigation()
    {
        $mail = new Mail();
        $mail->setFrom('"AAA\" code injection"@domain', 'Sender\'s name');
    }
}
