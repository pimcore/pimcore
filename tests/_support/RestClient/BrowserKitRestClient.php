<?php

namespace Pimcore\Tests\RestClient;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Pimcore\Tool\RestClient\AbstractRestClient;
use Symfony\Component\BrowserKit\Client;
use Symfony\Component\BrowserKit\Request as BrowserKitRequest;
use Symfony\Component\BrowserKit\Response as BrowserKitResponse;

/**
 * @deprecated
 */
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
    public function getJsonResponse($method, $uri, array $parameters = [], array $files = [], array $server = [], $content = null, $expectedStatus = 200)
    {
        try {
            return parent::getJsonResponse($method, $uri, $parameters, $files, $server, $content, $expectedStatus);
        } catch (\Exception $e) {
            codecept_debug(sprintf(
                '[BrowserKitRestClient] Failed response with message "%s" and status code %d. Body: %s',
                $e->getMessage(),
                $this->lastResponse ? $this->lastResponse->getStatusCode() : '',
                $this->lastResponse ? (string)$this->lastResponse->getBody() : ''
            ));

            throw $e;
        }
    }

    /**
     * @inheritDoc
     */
    public function getResponse($method, $uri, array $parameters = [], array $files = [], array $server = [], $content = null)
    {
        $uri = $this->prepareUri($uri);
        $parameters = $this->prepareParameters($parameters);
        $server = $this->prepareHeaders($server);

        if (count($parameters) > 0) {
            $query = http_build_query($parameters);

            if (false === strpos($uri, '?')) {
                $uri .= '?' . $query;
            } else {
                $uri .= '&' . $query;
            }
        }

        codecept_debug('[BrowserKitRestClient] Requesting URI ' . $uri);

        $this->client->request($method, $uri, $parameters, $files, $server, $content);

        /** @var BrowserKitRequest $browserKitRequest */
        $browserKitRequest = $this->client->getInternalRequest();

        /** @var BrowserKitResponse $response */
        $browserKitResponse = $this->client->getInternalResponse();

        $request = new Request(
            $browserKitRequest->getMethod(),
            $browserKitRequest->getUri(),
            //need to cast header values to string as only numeric and string are accepted by psr standard
            array_map(function ($value) {
                return (string) $value;
            }, $browserKitRequest->getServer()),
            $browserKitRequest->getContent()
        );

        $response = new Response(
            $browserKitResponse->getStatus(),
            $browserKitResponse->getHeaders(),
            $browserKitResponse->getContent()
        );

        $this->lastRequest = $request;
        $this->lastResponse = $response;

        return $response;
    }

    /**
     * @param string $uri
     *
     * @return string
     */
    protected function prepareUri($uri)
    {
        if ($this->basePath) {
            $uri = $this->basePath . $uri;
        }

        return $uri;
    }
}
