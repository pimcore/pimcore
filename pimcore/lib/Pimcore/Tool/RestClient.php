<?php

namespace Pimcore\Tool;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Pimcore\Tool\RestClient\AbstractRestClient;

/**
 * Standard RestClient working with a Guzzle client
 */
class RestClient extends AbstractRestClient
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @inheritDoc
     */
    public function __construct(Client $client, array $parameters = [], array $headers = [], array $options = [])
    {
        $this->client = $client;

        parent::__construct($parameters, $headers, $options);
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
        $uri        = $this->prepareUri($uri);
        $parameters = $this->prepareParameters($parameters);
        $server     = $this->prepareHeaders($server);

        $request  = $this->lastRequest = new Request($method, $uri, $server, $content);
        $response = $this->lastResponse = $this->client->send($request, [
            'query' => $parameters
        ]);

        return $response;
    }
}
