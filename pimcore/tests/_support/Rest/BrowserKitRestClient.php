<?php

namespace Pimcore\Tests\Rest;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Pimcore\Tool\RestClient\AbstractRestClient;
use Symfony\Component\BrowserKit\Client;
use Symfony\Component\BrowserKit\Request as BrowserKitRequest;
use Symfony\Component\BrowserKit\Response as BrowserKitResponse;

class BrowserKitRestClient extends AbstractRestClient
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
     * @inheritDoc
     */
    public function getResponse($method, $uri, array $parameters = [], array $files = [], array $server = [], $content = null)
    {
        $uri        = $this->prepareUri($uri);
        $parameters = $this->prepareParameters($parameters);
        $server     = $this->prepareHeaders($server);

        $this->client->request($method, $uri, $parameters, $files, $server, $content);

        /** @var BrowserKitRequest $browserKitRequest */
        $browserKitRequest = $this->client->getInternalRequest();

        /** @var BrowserKitResponse $response */
        $browserKitResponse = $this->client->getInternalResponse();

        $request = new Request(
            $browserKitRequest->getMethod(),
            $browserKitRequest->getUri(),
            $browserKitRequest->getServer(),
            $browserKitRequest->getContent()
        );

        $response = new Response(
            $browserKitResponse->getStatus(),
            $browserKitResponse->getHeaders(),
            $browserKitResponse->getContent()
        );

        $this->lastRequest  = $request;
        $this->lastResponse = $response;

        return $response;
    }
}
