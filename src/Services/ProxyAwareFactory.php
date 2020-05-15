<?php

namespace DocPlanner\Bundle\HubSpotBundle\Services;

use SevenShores\Hubspot\Factory as HubspotFactory;

class ProxyAwareFactory extends HubspotFactory
{
    public function __construct($config = [], $client = null, $clientOptions = [], $wrapResponse = true)
    {
        if (null !== $client)
        {
            $this->client = $client;
        }

        if ([] === $config['proxy'])
        {
            parent::__construct($config, $client, $clientOptions, $wrapResponse);
            return;
        }

		$this->client = new ProxyClient($config, null, $clientOptions, $wrapResponse);
    }

    public static function createClient(array $config = [], $client = null, $clientOptions = [], $wrapResponse = true)
    {
        return new static($config, $client, $clientOptions, $wrapResponse);
    }
}
