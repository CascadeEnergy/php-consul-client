<?php

namespace CascadeEnergy\ServiceDiscovery\Consul;

use CascadeEnergy\ServiceDiscovery\ServiceDiscoveryClientInterface;
use GuzzleHttp\Client;
use Composer\Semver\Semver;

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

    /**
     * @param $serviceName The name of the service to locate
     * @param null $version A semver version constraint, or null if no version constraint is required
     * @return bool|string The IP address and port of a suitable service instance, or false if no services were found
     */
    public function getServiceAddress($serviceName, $version = null)
    {
        $url = "http://{$this->consulUri}/v1/health/service/$serviceName?passing";

        $response = $this->httpClient->get($url);

        $data = json_decode(strval($response->getBody()));

        if (json_last_error() != JSON_ERROR_NONE || !is_array($data) || count($data) < 1) {
            return false;
        }

        if (!empty($version)) {
            $version = $this->normalizeVersion($version);
            $data = $this->filterVersions($data, $version);
        }

        if (count($data) < 1) {
            return false;
        }

        $service = $data[array_rand($data)];

        $ipAddress = $service->Service->Address;
        $port = $service->Service->Port;

        return "$ipAddress:$port";
    }
    
    private function filterVersions($serviceList, $targetVersion)
    {
        $versionPattern = '/\d+[-\\.]\d+[-\\.]\d+/';
        $matchingList = [];

        foreach($serviceList as $service) {
            $tags = $service->Service->Tags;

            foreach ($tags as $tag) {
                if (preg_match($versionPattern, $tag)) {
                    $tag = $this->normalizeVersion($tag);
                    if (Semver::satisfies($tag, $targetVersion)) {
                        $matchingList[] = $service;
                        break;
                    }
                }
            }
        }

        return $matchingList;
    }

    private function normalizeVersion($version) {
        return str_replace('-', '.', $version);
    }
}
