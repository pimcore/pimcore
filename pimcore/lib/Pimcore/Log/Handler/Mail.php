<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Log\Handler;

use Monolog\Logger;
use Monolog\Handler\MailHandler;
use Pimcore\Tool;

class Mail extends MailHandler {

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
        $mail = Tool::getMail(array($this->address),"pimcore log notification");
        $mail->setIgnoreDebugMode(true);
        $mail->setBodyText($content);
        $mail->send();
    }

}