<?php

namespace Cascade\ServiceDiscovery;

interface ServiceDiscoveryClientInterface
{
    public function getServiceAddress($serviceName, $version = null);
}
