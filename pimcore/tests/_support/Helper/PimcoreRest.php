<?php

namespace Pimcore\Tests\Helper;

use Codeception\Module\REST;
use Codeception\TestInterface;
use Pimcore\Model\User;
use Pimcore\Tests\Test\RestTestCase;
use Pimcore\Tests\Util\TestHelper;
use Pimcore\Tool\Authentication;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\BrowserKit\Response;

class PimcoreRest extends REST
{
    /**
     * @var User[]
     */
    protected $users = [];

    /**
     * @var array
     */
    protected $globalParams = [];

    /**
     * @inheritDoc
     */
    public function _initialize()
    {
        parent::_initialize();

        $this->initializeUser('rest', true);
    }

    public function _parts()
    {
        // only support json
        return ['json'];
    }


    public function _before(TestInterface $test)
    {
        parent::_before($test);

        // add global request params (e.g. authentication)
        if ($test instanceof RestTestCase) {
            $params = $test->getGlobalRequestParams();
            if ($params) {
                $this->globalParams = array_merge($this->globalParams, $params);
            }
        }
    }

    protected function resetVariables()
    {
        parent::resetVariables();

        $this->globalParams = [];
    }

    protected function execute($method, $url, $parameters = [], $files = [])
    {
        // add global parameters to request
        $parameters = array_merge($this->globalParams, $parameters);

        parent::execute($method, $url, $parameters, $files);
    }

    /**
     * @param string $method     The request method
     * @param string $uri        The URI to fetch
     * @param array  $parameters The Request parameters
     * @param array  $files      The files
     * @param array  $server     The server parameters (HTTP headers are referenced with a HTTP_ prefix as PHP does)
     * @param string $content    The raw body data
     *
     * @return Response
     */
    public function executeDirect($method, $uri, array $parameters = [], array $files = [], array $server = [], $content = null)
    {
        // add configured url prefix
        if ($this->config['url']) {
            $uri = $this->config['url'] . $uri;
        }

        // add global parameters to request
        $parameters = array_merge($this->globalParams, $parameters);

        $this->connectionModule->_request($method, $uri, $parameters, $files, $server, $content);
    }

    /**
     * @return \Symfony\Component\BrowserKit\Client|\Symfony\Component\HttpKernel\Client
     */
    public function getHttpClient()
    {
        return $this->client;
    }

    /**
     * @return Response|null
     */
    public function grabResponseObject()
    {
        return $this->getRunningClient()->getInternalResponse();
    }

    /**
     * @return Request|null
     */
    public function grabRequestObject()
    {
        return $this->getRunningClient()->getInternalRequest();
    }

    /**
     * Add API key param for username
     *
     * @param string $username
     */
    public function addApiKeyParam($username = 'rest')
    {
        $apiKey = $this->getRestApiKey($username);

        // add API key to global params when using the REST module directly
        $this->globalParams = array_merge($this->globalParams, [
            'apikey' => $apiKey
        ]);
    }

    /**
     * @param string $username
     *
     * @return User
     */
    public function getRestUser($username = 'rest')
    {
        if (isset($this->users[$username])) {
            return $this->users[$username];
        }

        throw new \InvalidArgumentException(sprintf('User %s does not exist', $username));
    }

    /**
     * @param string $username
     *
     * @return string
     */
    public function getRestApiKey($username = 'rest')
    {
        return $this->getRestUser($username)->getApiKey();
    }

    /**
     * @param string $username
     * @param bool $admin
     *
     * @return User
     */
    protected function initializeUser($username = 'rest', $admin = true)
    {
        if (!TestHelper::supportsDbTests()) {
            $this->debug(sprintf('[REST] Not initializing user %s as DB is not connected', $username));

            return null;
        } else {
            $this->debug(sprintf('[REST] Initializing user %s', $username));
        }

        $password = $username;

        /** @var User $user */
        $user = User::getByName($username);

        if (!$user) {
            $this->debug(sprintf('[REST] Creating user %s', $username));

            $apikey = md5(time()) . md5($username);
            $user   = User::create([
                'parentId' => 0,
                'username' => 'rest',
                'password' => Authentication::getPasswordHash($username, $password),
                'active'   => true,
                'apiKey'   => $apikey,
                'admin'    => $admin
            ]);
        }

        $this->users[$user->getName()] = $user;

        return $user;
    }
}
