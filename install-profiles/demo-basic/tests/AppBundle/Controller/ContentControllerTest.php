<?php

declare(strict_types=1);

namespace Tests\AppBundle\Controller;

use Pimcore\Test\WebTestCase;

class ContentControllerTest extends WebTestCase
{
    public function testRedirectFromEn()
    {
        $client = static::createClient();
        $client->request('GET', '/en');

        $this->assertTrue($client->getResponse()->isRedirect());

        $client->followRedirect();

        $this->assertEquals('/', $client->getRequest()->getPathInfo());
    }

    public function testPortal()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/');

        $response = $client->getResponse();

        $this->assertTrue($response->isSuccessful(), 'response status is 2xx');

        $this->assertTrue($response->headers->contains('X-Custom-Header', 'Foo'));
        $this->assertTrue($response->headers->contains('X-Custom-Header', 'Bar'));
        $this->assertTrue($response->headers->contains('X-Custom-Header2', 'Bazinga'));

        $this->assertEquals(
            1,
            $crawler->filter('h1:contains("Ready to be impressed?")')->count()
        );
    }
}
