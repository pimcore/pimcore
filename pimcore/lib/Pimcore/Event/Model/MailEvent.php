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

namespace Pimcore\Event\Model;

use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Pimcore\Mail;
use Symfony\Component\EventDispatcher\Event;

class MailEvent extends Event implements ElementEventInterface
{
    use ArgumentsAwareTrait;

    /**
     * @var Mail
     */
    protected $element;

    /**
     * DocumentEvent constructor.
     *
     * @param Mail $mail
     * @param array $arguments
     */
    public function __construct(Mail $mail, array $arguments = [])
    {
        $this->element = $mail;
        $this->arguments = $arguments;
    }

    /**
     * @return Mail
     */
    public function getElement()
    {
        return $this->element;
    }

    /**
     * @param Mail $element
     * @return $this
     */
    public function setElement($element)
    {
        $this->element = $element;
        return $this;
    }


}
