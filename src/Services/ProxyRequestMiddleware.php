<?php

namespace DocPlanner\Bundle\HubSpotBundle\Services;

use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use RuntimeException;

final class ProxyRequestMiddleware
{
	/**
	 * @param array<string, mixed> $customHeaders
	 */
	public static function create(string $customUrl, array $customHeaders): callable
	{
		$customHeaders = self::normalizeCustomHeaders($customHeaders);

		return Middleware::mapRequest(static function (RequestInterface $request) use (
			$customUrl,
			$customHeaders
		): RequestInterface {
			$parsedUrl = parse_url((string)$request->getUri());
			if (false === $parsedUrl)
			{
				throw new RuntimeException('Invalid HubSpot request URL');
			}

			$uri = vsprintf('%s%s?%s', [
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
		});
	}

	/**
	 * @param array<string, mixed> $customHeaders
	 *
	 * @return array<string, string>
	 */
	private static function normalizeCustomHeaders(array $customHeaders): array
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
