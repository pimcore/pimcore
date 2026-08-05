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

namespace Pimcore\Event\Model;

use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Pimcore\Mail;
use Symfony\Contracts\EventDispatcher\Event;

class MailEvent extends Event
{
    use ArgumentsAwareTrait;

    protected Mail $mail;

    public function __construct(Mail $mail, array $arguments = [])
    {
        $this->mail = $mail;
        $this->arguments = $arguments;
    }

    public function getMail(): Mail
    {
        return $this->mail;
    }

    public function setMail(Mail $mail): static
    {
        $this->mail = $mail;

        return $this;
    }
}
