<?php
/*
 * $Id: 495c02bc3a90d24694d8a4bf2d43ac077e0f9ec6 $
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://phing.info>.
 */

require_once 'phing/Task.php';

/**
 * A HTTP request task.
 * Making an HTTP request and try to match the response against an provided
 * regular expression.
 *
 * @package phing.tasks.ext
 * @author  Benjamin Schultz <bschultz@proqrent.de>
 * @version $Id: 495c02bc3a90d24694d8a4bf2d43ac077e0f9ec6 $
 * @since   2.4.1
 */
class HttpRequestTask extends Task
{
    /**
     * Holds the request URL
     *
     * @var string
     */
    protected $_url = null;

    /**
     * Holds the regular expression that should match the response
     *
     * @var string
     */
    protected $_responseRegex = '';

    /**
     * Whether to enable detailed logging
     *
     * @var boolean
     */
    protected $_verbose = false;

    /**
     * Holds additional header data
     *
     * @var array<Parameter>
     */
    protected $_headers = array();

    /**
     * Holds additional config data for HTTP_Request2
     *
     * @var array<Parameter>
     */
    protected $_configData = array();

    /**
     * Holds the authentication user name
     *
     * @var string
     */
    protected $_authUser = null;

    /**
     * Holds the authentication password
     *
     * @var string
     */
    protected $_authPassword = '';

    /**
     * Holds the authentication scheme
     *
     * @var string
     */
    protected $_authScheme;

    /**
     * Holds the events that will be logged
     *
     * @var array<string>
     */
    protected $_observerEvents = array(
        'connect',
        'sentHeaders',
        'sentBodyPart',
        'receivedHeaders',
        'receivedBody',
        'disconnect',
    );

    /**
     * Sets the request URL
     *
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->_url = $url;
    }

    /**
     * Sets the response regex
     *
     * @param string $regex
     */
    public function setResponseRegex($regex)
    {
        $this->_responseRegex = $regex;
    }

    /**
     * Sets the authentication user name
     *
     * @param string $user
     */
    public function setAuthUser($user)
    {
        $this->_authUser = $user;
    }

    /**
     * Sets the authentication password
     *
     * @param string $password
     */
    public function setAuthPassword($password)
    {
        $this->_authPassword = $password;
    }

    /**
     * Sets the authentication scheme
     *
     * @param string $scheme
     */
    public function setAuthScheme($scheme)
    {
        $this->_authScheme = $scheme;
    }

    /**
     * Sets whether to enable detailed logging
     *
     * @param boolean $verbose
     */
    public function setVerbose($verbose)
    {
        $this->_verbose = StringHelper::booleanValue($verbose);
    }

    /**
     * Sets a list of observer events that will be logged
     * if verbose output is enabled.
     *
     * @param string $observerEvents List of observer events
     *
     * @return void
     */
    public function setObserverEvents($observerEvents)
    {
        $this->_observerEvents = array();

        $token = ' ,;';
        $ext   = strtok($observerEvents, $token);

        while ($ext !== false) {
            $this->_observerEvents[] = $ext;
            $ext = strtok($token);
        }
    }

    /**
     * Creates an additional header for this task
     *
     * @return Parameter The created header
     */
    public function createHeader()
    {
        $num = array_push($this->_headers, new Parameter());
        return $this->_headers[$num-1];
    }

    /**
     * Creates a config parameter for this task
     *
     * @return Parameter The created parameter
     */
    public function createConfig()
    {
        $num = array_push($this->_configData, new Parameter());
        return $this->_configData[$num-1];
    }

    /**
     * Load the necessary environment for running this task.
     *
     * @throws BuildException
     */
    public function init()
    {
        @include_once 'HTTP/Request2.php';

        if (! class_exists('HTTP_Request2')) {
            throw new BuildException(
                'HttpRequestTask depends on HTTP_Request2 being installed '
                . 'and on include_path.',
                $this->getLocation()
            );
        }

        $this->_authScheme = HTTP_Request2::AUTH_BASIC;

        // Other dependencies that should only be loaded
        // when class is actually used
        require_once 'HTTP/Request2/Observer/Log.php';
    }

    /**
     * Make the http request
     */
    public function main()
    {
        if (!isset($this->_url)) {
            throw new BuildException("Missing attribute 'url' set");
        }

        $request = new HTTP_Request2($this->_url);

        // set the authentication data
        if (!empty($this->_authUser)) {
            $request->setAuth(
                $this->_authUser,
                $this->_authPassword,
                $this->_authScheme
            );
        }

        foreach ($this->_configData as $config) {
            $request->setConfig($config->getName(), $config->getValue());
        }

        foreach ($this->_headers as $header) {
            $request->setHeader($header->getName(), $header->getValue());
        }

        if ($this->_verbose) {
            $observer = new HTTP_Request2_Observer_Log();

            // set the events we want to log
            $observer->events = $this->_observerEvents;

            $request->attach($observer);
        }

        $response = $request->send();

        if ($this->_responseRegex !== '') {
            $matches = array();
            preg_match($this->_responseRegex, $response->getBody(), $matches);

            if (count($matches) === 0) {
                throw new BuildException(
                    'The received response body did not match the '
                    . 'given regular expression'
                );
            } else {
                $this->log('The response body matched the provided regex.');
            }
        }
    }
}