<?php

namespace Pimcore\Tests\Helper;

use Codeception\Module\REST;
use Codeception\TestInterface;
use Pimcore\Model\User;
use Pimcore\Tests\Test\RestTestCase;
use Pimcore\Tool\Authentication;

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
     * Add API key param for username
     *
     * @param string $username
     */
    public function addApiKeyParam($username = 'rest')
    {
        $this->globalParams = array_merge($this->globalParams, [
            'apikey' => $this->getRestApiKey($username)
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
