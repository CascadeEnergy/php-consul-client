<?php

namespace Cascade\Tests\ServiceDiscovery\Consul;

use Cascade\ServiceDiscovery\Consul\ConsulDns;

class ConsulDnsTest extends \PHPUnit_Framework_TestCase
{
    public function testItShouldReturnFalseIfTheDnsLookupReturnsNothing()
    {
        $consulDns = new ConsulDns(function () {
            return [];
        });

        $this->assertFalse($consulDns->getServiceAddress('foo', 'bar'));
    }

    public function testItShouldReturnTheIpAddressFromTheARecordWithADefaultPortIfNoPortIsFound()
    {
        $consulDns = new ConsulDns(function () {
            return [['ip' => '1.2.3.4']];
        });

        $this->assertEquals('1.2.3.4:80', $consulDns->getServiceAddress('foo', 'bar'));
    }

    public function testItShouldReturnThePortIfOneIsFound()
    {
        $consulDns = new ConsulDns(function () {
            return [['ip' => '1.2.3.4'], ['port' => 42]];
        });

        $this->assertEquals('1.2.3.4:42', $consulDns->getServiceAddress('foo', 'bar'));
    }

    public function testItShouldGenerateADnsNameFromTheGivenServiceName()
    {
        $consulDns = new ConsulDns(function ($name) {
            $this->assertEquals($name, 'foo.service.consul');

            return [];
        });

        $consulDns->getServiceAddress('foo');
    }

    public function testItShouldIncludeTheVersionTagInTheQueryIfOneIsProvided()
    {
        $consulDns = new ConsulDns(function ($name) {
            $this->assertEquals($name, 'bar.foo.service.consul');

            return [];
        });

        $consulDns->getServiceAddress('foo', 'bar');
    }

    public function testItShouldUseTheDnsCheckRecordFunctionByDefault()
    {
        $consulDns = new ConsulDns();

        $this->assertAttributeEquals('dns_check_record', 'lookupService', $consulDns);
    }
}
