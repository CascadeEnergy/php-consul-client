<?php

namespace CascadeEnergy\Tests\ServiceDiscovery\Consul;

use CascadeEnergy\ServiceDiscovery\Consul\ConsulApi;

class ConsulApiTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConsulApi */
    private $consulApi;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $httpClient;

    public function setUp()
    {
        $this->httpClient = $this->getMock('GuzzleHttp\Client', ['get']);

        /** @noinspection PhpParamsInspection */
        $this->consulApi = new ConsulApi($this->httpClient, 'http://foo.bar.baz');
    }

    public function testItShouldReturnARandomServiceFromTheResults()
    {
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');

        $resultList = [
            ['Service' => ['Address' => 'alpha', 'Port' => 42]],
            ['Service' => ['Address' => 'beta', 'Port' => 84]]
        ];

        $response->expects($this->once())->method('getBody')->willReturn(json_encode($resultList));

        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->with('http://foo.bar.baz/v1/health/service/qux?passing')
            ->willReturn($response);

        $address = $this->consulApi->getServiceAddress('qux');

        $this->logicalOr(
            $this->equalTo('alpha:42'),
            $this->equalTo('beta:84')
        )->evaluate($address);
    }

    public function testItShouldPassThroughAVersionIdIfOneIsProvided()
    {
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');
        $resultList = [['Service' => ['Address' => 'alpha', 'Port' => 42]]];
        $response->expects($this->once())->method('getBody')->willReturn(json_encode($resultList));

        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->with('http://foo.bar.baz/v1/health/service/qux?passing&tag=bagel.burrito')
            ->willReturn($response);

        $this->consulApi->getServiceAddress('qux', 'bagel.burrito');
    }

    public function testIfTheResponseIsNotJsonFalseShouldBeReturned()
    {
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');
        $response->expects($this->once())->method('getBody')->willReturn('orange-monkey');
        $this->httpClient->expects($this->once())->method('get')->willReturn($response);
        $this->assertFalse($this->consulApi->getServiceAddress('qux'));
    }
}
