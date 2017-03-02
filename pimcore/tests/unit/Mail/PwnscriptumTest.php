<?php

namespace Pimcore\Tests\Unit\Mail;

use Pimcore\Mail;
use Pimcore\Tests\Test\TestCase;

/**
 * TODO how valid is this test now that we use swiftmailer?
 */
class PwnscriptumTest extends TestCase
{
    public function testNormalFrom()
    {
        $email = 'foo@example.com';
        $name  = 'Sender\'s name';

        $mail = new Mail();
        $mail->setFrom($email, $name);

        $expected = [];
        $expected[$email] = $name;

        $this->assertEquals($expected, $mail->getFrom());
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
