<?php

namespace DocPlanner\Bundle\HubSpotBundle\Tests\Services;

use DocPlanner\Bundle\HubSpotBundle\Services\ProxyRequestMiddleware;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

final class ProxyRequestMiddlewareTest extends TestCase
{
	public function testItProxiesRequestUriAndAddsCustomHeaders(): void
	{
		$proxiedRequest = $this->mapRequest(
			new Request('GET', 'https://api.hubapi.com/contacts/v1/contact?hapikey=dummy'),
			'https://proxy.example.com/hubspot-gateway',
			[
				'x-api-key'     => 'test-api-key',
				'Authorization' => 'Bearer private-app-token',
			]
		);

		self::assertSame(
			'https://proxy.example.com/hubspot-gateway/contacts/v1/contact?hapikey=dummy',
			(string)$proxiedRequest->getUri()
		);
		self::assertSame('test-api-key', $proxiedRequest->getHeaderLine('x-api-key'));
		self::assertSame('Bearer private-app-token', $proxiedRequest->getHeaderLine('Authorization'));
	}

	public function testItSkipsEmptyOptionalHeaders(): void
	{
		$proxiedRequest = $this->mapRequest(
			new Request('POST', 'https://api.hubapi.com/crm/v3/objects/contacts'),
			'https://proxy.example.com/hubspot-gateway',
			[
				'x-api-key'     => 'test-api-key',
				'Authorization' => 'Bearer ',
				'x-empty'       => '',
				'x-null'        => null,
			]
		);

		self::assertSame(
			'https://proxy.example.com/hubspot-gateway/crm/v3/objects/contacts',
			(string)$proxiedRequest->getUri()
		);
		self::assertSame('test-api-key', $proxiedRequest->getHeaderLine('x-api-key'));
		self::assertFalse($proxiedRequest->hasHeader('Authorization'));
		self::assertFalse($proxiedRequest->hasHeader('x-empty'));
		self::assertFalse($proxiedRequest->hasHeader('x-null'));
	}

	/**
	 * @param array<string, mixed> $headers
	 */
	private function mapRequest(RequestInterface $request, string $customUrl, array $headers): RequestInterface
	{
		$capturedRequest = null;
		$middleware      = ProxyRequestMiddleware::create($customUrl, $headers);
		$handler         = $middleware(static function (RequestInterface $request) use (&$capturedRequest): RequestInterface {
			$capturedRequest = $request;

			return $request;
		});

		$handler($request, []);

		self::assertInstanceOf(RequestInterface::class, $capturedRequest);

		return $capturedRequest;
	}
}
