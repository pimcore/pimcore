<?php

namespace Pimcore\Tool;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
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

    /**
     * @param Uri|string $uri
     *
     * @return Uri
     */
    protected function prepareUri($uri)
    {
        if (!($uri instanceof Uri)) {
            $uri = new Uri($uri);
        }

        if ($this->getScheme()) {
            $uri = $uri->withScheme($this->getScheme());
        }

        if ($this->getHost()) {
            $uri = $uri->withHost($this->getHost());
        }

        if ($this->getPort()) {
            $uri = $uri->withPort($this->getPort());
        }

        if ($this->getBasePath()) {
            $uri = $uri->withPath($this->getBasePath() . $uri->getPath());
        }

        return $uri;
    }
}
