<?php

namespace CascadeEnergy\ServiceDiscovery;

interface ServiceDiscoveryClientInterface
{
    public function getServiceAddress($serviceName, $version = null);
}
