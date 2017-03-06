<?php

namespace Pimcore\Tool;

use Pimcore\Tool\RestClient\AbstractRestClient;
use Pimcore\Tool\RestClient\Exception;
use Symfony\Component\BrowserKit\Client;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\BrowserKit\Response;

/**
 * Standard RestClient working with a BrowserKit client
 */
class RestClient extends AbstractRestClient
{
    /**
     * @var string
     */
    protected $basePath = '/webservice/rest';

    /**
     * @var string
     */
    protected $apikey;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @param Client $client
     * @param array  $options
     */
    public function __construct(Client $client, array $options = [])
    {
        $this->client = $client;

        parent::__construct($options);
    }

    /**
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * @param string $basePath
     */
    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;
    }

    /**
     * @param $apikey
     *
     * @return $this
     */
    public function setApiKey($apikey)
    {
        $this->apikey = $apikey;

        return $this;
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->apikey;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @inheritDoc
     */
    public function getResponse($method, $uri, array $parameters = [], array $files = [], array $server = [], $content = null)
    {
        if ($this->basePath) {
            $uri = $this->basePath . $uri;
        }

        $parameters = $this->buildClientParameters($parameters);

        $this->client->request($method, $uri, $parameters, $files, $server, $content);

        /** @var Response $response */
        $response = $this->client->getInternalResponse();

        return $response;
    }

    /**
     * @inheritDoc
     */
    public function getJsonResponse($method, $uri, array $parameters = [], array $files = [], array $server = [], $content = null, $expectedStatus = 200)
    {
        $response = $this->getResponse($method, $uri, $parameters, $files, $server, $content);

        $json = $this->parseJsonResponse($response, $this->client->getInternalRequest());

        return $json;
    }

    /**
     * @param Response $response
     * @param Request  $request
     * @param int      $expectedStatus
     *
     * @return object
     * @throws Exception
     */
    protected function parseJsonResponse(Response $response = null, Request $request = null, $expectedStatus = 200)
    {
        /** @var Response $response */
        if (null === $response) {
            $response = $this->client->getInternalResponse();
        }

        /** @var Request $request */
        if (null === $request) {
            $request = $this->client->getInternalRequest();
        }

        if ($response->getStatus() !== $expectedStatus) {
            throw Exception::create(
                sprintf('Response status does not match the expected status: %d', $response->getStatus()),
                $request,
                $response
            );
        }

        if ($response->getHeader('Content-Type') !== 'application/json') {
            throw Exception::create(
                sprintf(
                    'No JSON response header (%s): %d %s',
                    $response->getHeader('Content-Type'),
                    $response->getStatus(),
                    $request->getUri()
                ),
                $request,
                $response
            );
        }

        $json = null;
        if (!empty($response->getContent())) {
            $json = json_decode($json);
        }

        if (null === $json) {
            throw Exception::create(
                sprintf('No valid JSON data: %s', $response->getContent()),
                $request,
                $response
            );
        }

        return $json;
    }

    /**
     * Add client parameters
     *
     * @param array $parameters
     *
     * @return array
     */
    protected function buildClientParameters(array $parameters = [])
    {
        if ($this->getApiKey()) {
            $parameters['apikey'] = $this->getApiKey();
        }

        return $parameters;
    }
}
