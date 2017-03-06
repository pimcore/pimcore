<?php

namespace Pimcore\Tests\Rest;

use Pimcore\Tests\RestTester;
use Pimcore\Tool\RestClient\AbstractRestClient;
use Pimcore\Tool\RestClient\Exception;
use Symfony\Component\BrowserKit\Response;

/**
 * Test RestClient handling requests via REST codeception module
 */
class RestClient extends AbstractRestClient
{
    /**
     * @var RestTester
     */
    protected $tester;

    /**
     * @param RestTester $tester
     * @param array      $options
     */
    public function __construct(RestTester $tester, array $options = [])
    {
        $this->tester = $tester;

        parent::__construct($options);
    }

    /**
     * @return RestTester
     */
    public function getTester()
    {
        return $this->tester;
    }

    /**
     * @inheritDoc
     */
    public function getResponse($method, $uri, array $parameters = [], array $files = [], array $server = [], $content = null)
    {
        $this->tester->executeDirect($method, $uri, $parameters, $files, $server, $content);

        /** @var Response $response */
        $response = $this->tester->grabResponseObject();

        return $response;
    }

    /**
     * @inheritDoc
     */
    public function getJsonResponse($method, $uri, array $parameters = [], array $files = [], array $server = [], $content = null, $expectedStatus = 200)
    {
        $this->getResponse($method, $uri, $parameters, $files, $server, $content);

        $json = $this->parseJsonResponse($expectedStatus);

        return $json;
    }

    /**
     * @param int $expectedStatus
     *
     * @return object
     * @throws Exception
     */
    protected function parseJsonResponse($expectedStatus)
    {
        $this->tester->canSeeResponseIsJson();
        $this->tester->canSeeResponseCodeIs($expectedStatus);

        $json = null;
        if (!empty($response = $this->tester->grabResponse())) {
            $json = json_decode($response);
        }

        if (null === $json) {
            throw Exception::create(
                sprintf('No valid JSON data: %s', $this->tester->grabResponse()),
                $this->tester->grabRequestObject(),
                $this->tester->grabResponseObject()
            );
        }

        return $json;
    }
}
