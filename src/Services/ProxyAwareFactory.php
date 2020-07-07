<?php

namespace DocPlanner\Bundle\HubSpotBundle\Services;

use SevenShores\Hubspot\Factory as HubspotFactory;

class ProxyAwareFactory extends HubspotFactory
{
	/** @inheritDoc */
	public function __construct($config = [], $client = null, $clientOptions = [], $wrapResponse = true)
	{
		if (null !== $client)
		{
			$this->client = $client;
		}

		if (!array_key_exists('proxy', $config) || [] === $config['proxy'])
		{

			if (!array_key_exists('key', $config))
			{
				throw new \RuntimeException('You need provide hubspot api key or proxy configuration');
			}

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
