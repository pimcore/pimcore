<?php

namespace Pimcore\Tests\Unit\Mail;

use Pimcore\Mail;
use Pimcore\Tests\Test\TestCase;

class PwnscriptumTest extends TestCase
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
