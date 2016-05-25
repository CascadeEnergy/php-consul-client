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

    public function testItShouldFilterServicesBySemverConstraints()
    {
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');
        $resultList = $this->getServiceListWithVersions();
        $response->expects($this->once())->method('getBody')->willReturn(json_encode($resultList));

        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->with('http://foo.bar.baz/v1/health/service/qux?passing')
            ->willReturn($response);

        $result = $this->consulApi->getServiceAddress('qux', '^1.0.0');

        $this->assertEquals('alpha:42', $result);
    }

    public function testItShouldApplyAllVersionTagsIfAServiceHasMoreThanOne()
    {
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');
        $resultList = $this->getServiceListWithVersions();
        $response->expects($this->exactly(2))->method('getBody')->willReturn(json_encode($resultList));

        $this->httpClient
            ->expects($this->exactly(2))
            ->method('get')
            ->with('http://foo.bar.baz/v1/health/service/qux?passing')
            ->willReturn($response);

        $result = $this->consulApi->getServiceAddress('qux', '^3.0.0');
        $this->assertEquals('gamma:62', $result);

        $result = $this->consulApi->getServiceAddress('qux', '^2-2-0');
        $this->assertEquals('gamma:62', $result);
    }

    public function testItShouldReturnFalseIfNoServiceSatisfiesTheSemverConstraint()
    {
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');
        $resultList = $this->getServiceListWithVersions();
        $response->expects($this->once())->method('getBody')->willReturn(json_encode($resultList));

        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->with('http://foo.bar.baz/v1/health/service/qux?passing')
            ->willReturn($response);

        $result = $this->consulApi->getServiceAddress('qux', '=1-1-0');
        $this->assertFalse($result);
    }

    public function testHyphensAndDotsShouldBeInterchangableInVersionNumbers()
    {
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');
        $resultList = $this->getServiceListWithVersions();
        $response->expects($this->once())->method('getBody')->willReturn(json_encode($resultList));

        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->with('http://foo.bar.baz/v1/health/service/qux?passing')
            ->willReturn($response);

        $result = $this->consulApi->getServiceAddress('qux', '=1.0-0');
        $this->assertEquals('alpha:42', $result);
    }

    public function testItShouldReturnARandomMatchingServiceIfThereAreMoreThanOneMatchingServices()
    {
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');
        $resultList = $this->getServiceListWithVersions();
        $response->expects($this->once())->method('getBody')->willReturn(json_encode($resultList));

        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->with('http://foo.bar.baz/v1/health/service/qux?passing')
            ->willReturn($response);

        $result = $this->consulApi->getServiceAddress('qux', '^2.0-0');
        $this->assertThat(
            $result,
            $this->logicalOr($this->equalTo('beta:52'), $this->equalTo('gamma:62'))
        );
    }

    public function testIfTheResponseIsNotJsonFalseShouldBeReturned()
    {
        $response = $this->getMock('Psr\Http\Message\ResponseInterface');
        $response->expects($this->once())->method('getBody')->willReturn('orange-monkey');
        $this->httpClient->expects($this->once())->method('get')->willReturn($response);
        $this->assertFalse($this->consulApi->getServiceAddress('qux'));
    }

    private function getServiceListWithVersions()
    {
        return [
            ['Service' => ['Address' => 'alpha', 'Port' => 42, 'Tags' => ['1-0-0']]],
            ['Service' => ['Address' => 'beta', 'Port' => 52, 'Tags' => ['2.0.0']]],
            ['Service' => ['Address' => 'gamma', 'Port' => 62, 'Tags' => ['3.1.0', '2-3-0']]]
        ];
    }
}
