<?php

namespace CascadeEnergy\ServiceDiscovery\Consul;

use CascadeEnergy\ServiceDiscovery\ServiceDiscoveryClientInterface;
use GuzzleHttp\Client;

class ConsulApi implements ServiceDiscoveryClientInterface
{
    /** @var Client */
    private $httpClient;

    /** @var string */
    private $consulUri;

    public function __construct(Client $httpClient, $consulUri)
    {
        $this->httpClient = $httpClient;
        $this->consulUri = $consulUri;
    }

    public function getServiceAddress($serviceName, $version = null)
    {
        $url = "{$this->consulUri}/v1/health/service/$serviceName?passing";

        if (!empty($version)) {
            $url .= "&tag=$version";
        }

        $response = $this->httpClient->get($url);

        $data = json_decode(strval($response->getBody()));

        if (json_last_error() != JSON_ERROR_NONE || !is_array($data) || count($data) < 1) {
            return false;
        }

        $service = $data[array_rand($data)];

        $ipAddress = $service->Service->Address;
        $port = $service->Service->Port;

        return "$ipAddress:$port";
    }
}
