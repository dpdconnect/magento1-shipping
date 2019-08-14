<?php

namespace DpdConnect\Sdk\Test;

use DpdConnect\Sdk\ClientBuilder;
use PHPUnit\Framework\TestCase;
use DpdConnect\Sdk\DpdConnectClientBuilder;
use DpdConnect\Sdk\DpdConnectClientInterface;
use DpdConnect\Sdk\Api\AuthenticationApi;
use donatj\MockWebServer\MockWebServer;
use donatj\MockWebServer\Response;
use donatj\MockWebServer\ResponseStack;

abstract class ApiTestCase extends TestCase
{
    /** @var MockWebServer */
    protected $server;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->server = new MockWebServer(8081, '127.0.0.1');
        $this->server->start();

        $this->server->setResponseOfPath(
            '/'. AuthenticationApi::AUTH_LOGIN_URI,
            new ResponseStack(
                new Response($this->getAuthenticatedJson())
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $this->server->stop();
    }

    /**
     * @return ClientInterface
     */
    protected function createClient()
    {
        $clientBuilder = new ClientBuilder($this->server->getServerRoot());

        return $clientBuilder->buildAuthenticatedByPassword(
            'username',
            'password'
        );
    }

    private function getAuthenticatedJson()
    {
        return <<<JSON
            {
                "token" : "this-is-a-jwt-token",
            }
JSON;
    }
}
