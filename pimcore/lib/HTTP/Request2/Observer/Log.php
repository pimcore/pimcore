<?php
/**
 * An observer useful for debugging / testing.
 *
 * PHP version 5
 *
 * LICENSE:
 *
 * Copyright (c) 2008, 2009, Alexey Borzov <avb@php.net>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * The names of the authors may not be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS
 * IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,
 * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
 * OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category HTTP
 * @package  HTTP_Request2
 * @author   David Jean Louis <izi@php.net>
 * @author   Alexey Borzov <avb@php.net>
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 * @version  CVS: $Id: Log.php,v 1.1 2009/01/02 16:27:14 avb Exp $
 * @link     http://pear.php.net/package/HTTP_Request2
 */

/**
 * Exception class for HTTP_Request2 package
 */ 
require_once 'HTTP/Request2/Exception.php';

/**
 * A debug observer useful for debugging / testing.
 *
 * This observer logs to a log target data corresponding to the various request 
 * and response events, it logs by default to php://output but can be configured
 * to log to a file or via the PEAR Log package.
 *
 * A simple example:
 * <code>
 * require_once 'HTTP/Request2.php';
 * require_once 'HTTP/Request2/Observer/Log.php';
 *
 * $request  = new HTTP_Request2('http://www.example.com');
 * $observer = new HTTP_Request2_Observer_Log();
 * $request->attach($observer);
 * $request->send();
 * </code>
 *
 * A more complex example with PEAR Log:
 * <code>
 * require_once 'HTTP/Request2.php';
 * require_once 'HTTP/Request2/Observer/Log.php';
 * require_once 'Log.php';
 *
 * $request  = new HTTP_Request2('http://www.example.com');
 * // we want to log with PEAR log
 * $observer = new HTTP_Request2_Observer_Log(Log::factory('console'));
 *
 * // we only want to log received headers
 * $observer->events = array('receivedHeaders');
 *
 * $request->attach($observer);
 * $request->send();
 * </code>
 *
 * @category HTTP
 * @package  HTTP_Request2
 * @author   David Jean Louis <izi@php.net>
 * @author   Alexey Borzov <avb@php.net>
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: 0.4.0
 * @link     http://pear.php.net/package/HTTP_Request2
 */
class HTTP_Request2_Observer_Log implements SplObserver
{
    // properties {{{

    /**
     * The log target, it can be a a resource or a PEAR Log instance.
     *
     * @var resource|Log $target
     */
    protected $target = null;

    /**
     * The events to log.
     *
     * @var array $events
     */
    public $events = array(
        'connect',
        'sentHeaders',
        'sentBodyPart',
        'receivedHeaders',
        'receivedBody',
        'disconnect',
    );

    // }}}
    // __construct() {{{

    /**
     * Constructor.
     *
     * @param mixed $target Can be a file path (default: php://output), a resource,
     *                      or an instance of the PEAR Log class.
     * @param array $events Array of events to listen to (default: all events)
     *
     * @return void
     */
    public function __construct($target = 'php://output', array $events = array())
    {
        if (!empty($events)) {
            $this->events = $events;
        }
        if (is_resource($target) || $target instanceof Log) {
            $this->target = $target;
        } elseif (false === ($this->target = @fopen($target, 'w'))) {
            throw new HTTP_Request2_Exception("Unable to open '{$target}'");
        }
    }

    // }}}
    // update() {{{

    /**
     * Called when the request notify us of an event.
     *
     * @param HTTP_Request2 $subject The HTTP_Request2 instance
     *
     * @return void
     */
    public function update(SplSubject $subject)
    {
        $event = $subject->getLastEvent();
        if (!in_array($event['name'], $this->events)) {
            return;
        }

        switch ($event['name']) {
        case 'connect':
            $this->log('* Connected to ' . $event['data']);
            break;
        case 'sentHeaders':
            $headers = explode("\r\n", $event['data']);
            array_pop($headers);
            foreach ($headers as $header) {
                $this->log('> ' . $header);
            }
            break;
        case 'sentBodyPart':
            $this->log('> ' . $event['data']);
            break;
        case 'receivedHeaders':
            $this->log(sprintf('< HTTP/%s %s %s',
                $event['data']->getVersion(),
                $event['data']->getStatus(),
                $event['data']->getReasonPhrase()));
            $headers = $event['data']->getHeader();
            foreach ($headers as $key => $val) {
                $this->log('< ' . $key . ': ' . $val);
            }
            $this->log('< ');
            break;
        case 'receivedBody':
            $this->log($event['data']->getBody());
            break;
        case 'disconnect':
            $this->log('* Disconnected');
            break;
        }
    }
    
    // }}}
    // log() {{{

    /**
     * Log the given message to the configured target.
     *
     * @param string $message Message to display
     *
     * @return void
     */
    protected function log($message)
    {
        if ($this->target instanceof Log) {
            $this->target->debug($message);
        } elseif (is_resource($this->target)) {
            fwrite($this->target, $message . "\r\n");
        }
    }

    // }}}
}

?>