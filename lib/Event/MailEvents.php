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

namespace Pimcore\Event;

final class MailEvents
{
    /**
     * Arguments:
     *  - mailer | Swift_Mailer | contains the mailer object. Modify (or unset) this parameter if you want to implement a custom mail sending method
     *
     * @Event("Pimcore\Event\Model\MailEvent")
     *
     * @var string
     */
    const PRE_SEND = 'pimcore.mail.preSend';
}
