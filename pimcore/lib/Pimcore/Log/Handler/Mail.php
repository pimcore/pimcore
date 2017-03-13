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

namespace Pimcore\Log\Handler;

use Monolog\Logger;
use Monolog\Handler\MailHandler;
use Pimcore\Tool;

class Mail extends MailHandler
{

    /**
     * @var null
     */
    protected $address = null;

    /**
     * Mail constructor.
     * @param int $address
     * @param bool|int $level
     * @param bool|true $bubble
     */
    public function __construct($address, $level = Logger::DEBUG, $bubble = true)
    {
        $this->address = $address;
        parent::__construct($level, $bubble);
    }

    /**
     * @param string $content
     * @param array $records
     */
    public function send($content, array $records)
    {
        $mail = Tool::getMail([$this->address], "pimcore log notification");
        $mail->setIgnoreDebugMode(true);
        $mail->setBodyText($content);
        $mail->send();
    }
}
