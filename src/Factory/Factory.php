<?php

declare(strict_types=1);

namespace DocPlanner\Bundle\HubSpotBundle\Factory;

use SevenShores\Hubspot\Factory as HubspotFactory;

class Factory extends HubspotFactory
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



    }

    public static function createClient(array $config = [], $client = null, $clientOptions = [], $wrapResponse = true)
    {
        return new static($config, $client, $clientOptions, $wrapResponse);
    }
}
