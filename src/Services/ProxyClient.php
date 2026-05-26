<?php

namespace DocPlanner\Bundle\HubSpotBundle\Services;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use RuntimeException;
use SevenShores\Hubspot\Http\Client as HubspotClient;

class ProxyClient extends HubspotClient
{
	/** @inheritDoc */
	public function __construct($config = [], $client = null, $clientOptions = [], $wrapResponse = true)
	{
		if (null !== $this->client)
		{
			return;
		}

		if (!array_key_exists('key', $config) || empty($config['key']))
		{
			$config['key'] = 'thisKeyIsRequiredByExternalLibrary';
		}

		parent::__construct($config, $client, $clientOptions, $wrapResponse);

		$proxyConfig = $config['proxy'];
		if (!is_array($proxyConfig))
		{
			throw new RuntimeException('Invalid HubSpot proxy configuration');
		}

		$customUrl = $proxyConfig['custom_url'] ?? null;
		if (!is_string($customUrl))
		{
			throw new RuntimeException('Invalid HubSpot proxy URL configuration');
		}

		$customHeaders = $proxyConfig['custom_headers'] ?? [];
		if (!is_array($customHeaders))
		{
			throw new RuntimeException('Invalid HubSpot proxy headers configuration');
		}

		$handlerStack = HandlerStack::create();
		$handlerStack->push(ProxyRequestMiddleware::create($customUrl, $customHeaders));

		$this->client = new GuzzleClient(['handler' => $handlerStack]);
	}

	protected function generateUrl($endpoint, $query_string = null, $requires_auth = true)
	{
		return parent::generateUrl($endpoint, $query_string, false);
	}
}
