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

namespace Pimcore\Event;

final class MailEvents
{
    /**
     * Arguments:
     *  - mailer | \Pimcore\Mail\Mailer | contains the mailer object. Modify (or unset) this parameter if you want to implement a custom mail sending method
     *
     * @Event("Pimcore\Event\Model\MailEvent")
     *
     * @var string
     */
    const PRE_SEND = 'pimcore.mail.preSend';

    const PRE_LOG = 'pimcore.mail.preLog';
}
