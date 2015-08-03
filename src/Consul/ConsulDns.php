<?php

namespace Cascade\ServiceDiscovery\Consul;

use Cascade\ServiceDiscovery\ServiceDiscoveryClientInterface;

class ConsulDns implements ServiceDiscoveryClientInterface
{
    private $lookupService;

    public function __construct(callable $lookupService = null)
    {
        $this->lookupService = $lookupService;

        if (is_null($lookupService)) {
            $this->lookupService = "dns_check_record";
        }
    }

    public function getServiceAddress($serviceName, $version = null)
    {
        $dnsEntry = "$serviceName.service.consul";

        if (!empty($version)) {
            $dnsEntry = "$version.$dnsEntry";
        }

        $resultList = call_user_func($this->lookupService, $dnsEntry, DNS_SRV + DNS_A);

        if (empty($resultList)) {
            return false;
        }

        $ipAddress = '';
        $port = '80';

        foreach ($resultList as $result) {
            if (isset($result['ip'])) {
                $ipAddress = $result['ip'];
            }

            if (isset($result['port'])) {
                $port = $result['port'];
            }
        }

        return "$ipAddress:$port";
    }
}
