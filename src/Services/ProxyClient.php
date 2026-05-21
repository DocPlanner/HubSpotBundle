<?php

namespace DocPlanner\Bundle\HubSpotBundle\Services;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
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

		$customHeaders = $this->normalizeCustomHeaders($customHeaders);

		$handlerStack = HandlerStack::create();
		$handlerStack->push(Middleware::mapRequest(function (RequestInterface $request) use (
			$customUrl,
			$customHeaders
		) {

			$parsedUrl = parse_url((string)$request->getUri());
			if (false === $parsedUrl)
			{
				throw new RuntimeException('Invalid HubSpot request URL');
			}

			$uri       = vsprintf('%s%s?%s', [
				$customUrl,
				$parsedUrl['path'] ?? '',
				$parsedUrl['query'] ?? '',
			]);

			$request = $request->withUri(new Uri($uri));

			foreach ($customHeaders as $customHeaderName => $customHeaderValue)
			{
				$request = $request->withHeader($customHeaderName, $customHeaderValue);
			}

			return $request;
		}));

		$this->client = new GuzzleClient(['handler' => $handlerStack]);
	}

	protected function generateUrl($endpoint, $query_string = null, $requires_auth = true)
	{
		return parent::generateUrl($endpoint, $query_string, false);
	}

	/**
	 * @param array<string, mixed> $customHeaders
	 *
	 * @return array<string, string>
	 */
	private function normalizeCustomHeaders(array $customHeaders): array
	{
		$normalizedHeaders = [];

		foreach ($customHeaders as $name => $value)
		{
			if (null === $value || !is_scalar($value))
			{
				continue;
			}

			$value = trim((string)$value);
			if ('' === $value || 'Bearer' === $value)
			{
				continue;
			}

			$normalizedHeaders[$name] = $value;
		}

		return $normalizedHeaders;
	}
}
