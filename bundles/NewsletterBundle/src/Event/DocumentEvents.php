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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\NewsletterBundle\Event;

class DocumentEvents
{
    /**
     * Arguments:
     *  - mail | \Pimcore\Mail | the pimcore mail instance
     *  - document | \Pimcore\Model\Document\Newsletter | the newsletter document
     *  - sendingContainer | \Pimcore\Document\Newsletter | sending param container of newsletter helper
     *  - mailer | \Pimcore\Mail\Mailer|null | newsletter specific mailer if enabled in system settings
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    public const NEWSLETTER_PRE_SEND = 'pimcore.document.newsletter.pre_send';

    /**
     * Arguments:
     *  - mail | \Pimcore\Mail | the pimcore mail instance
     *  - document | \Pimcore\Model\Document\Newsletter | the newsletter document
     *  - sendingContainer | \Pimcore\Document\Newsletter | sending param container of newsletter helper
     *  - mailer | \Pimcore\Mail\Mailer|null | newsletter specific swift mailer if enabled in system settings
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    public const NEWSLETTER_POST_SEND = 'pimcore.document.newsletter.post_send';
}
