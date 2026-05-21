<?php

namespace DocPlanner\Bundle\HubSpotBundle\Tests\Services;

use DocPlanner\Bundle\HubSpotBundle\Services\ProxyClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

final class ProxyClientTest extends TestCase
{
	public function testItUsesProxyMiddlewareWithoutAddingLegacyHubspotApiKey(): void
	{
		$history = [];
		$client  = new ProxyClient(
			[
				'key'   => 'legacy-key',
				'proxy' => [
					'custom_url'     => 'https://proxy.example.com/hubspot-gateway',
					'custom_headers' => [
						'x-api-key'     => 'test-api-key',
						'Authorization' => 'Bearer private-app-token',
					],
				],
			],
			null,
			[],
			false
		);

		$handlerStack = $client->client->getConfig('handler');
		self::assertInstanceOf(HandlerStack::class, $handlerStack);
		$handlerStack->setHandler(new MockHandler([new Response(200, [], '{}')]));
		$handlerStack->push(Middleware::history($history));

		$client->request('GET', '/contacts/v1/contact', [], 'vid=123', true);

		self::assertCount(1, $history);

		/** @var RequestInterface $request */
		$request = $history[0]['request'];
		self::assertSame(
			'https://proxy.example.com/hubspot-gateway/contacts/v1/contact?vid=123',
			(string)$request->getUri()
		);
		self::assertSame('test-api-key', $request->getHeaderLine('x-api-key'));
		self::assertSame('Bearer private-app-token', $request->getHeaderLine('Authorization'));
		self::assertStringNotContainsString('hapikey=', (string)$request->getUri());
	}

	public function testItDoesNotSendEmptyAuthorizationHeaderFromProxyClient(): void
	{
		$history = [];
		$client  = new ProxyClient(
			[
				'proxy' => [
					'custom_url'     => 'https://proxy.example.com/hubspot-gateway',
					'custom_headers' => [
						'x-api-key'     => 'test-api-key',
						'Authorization' => 'Bearer ',
					],
				],
			],
			null,
			[],
			false
		);

		$handlerStack = $client->client->getConfig('handler');
		self::assertInstanceOf(HandlerStack::class, $handlerStack);
		$handlerStack->setHandler(new MockHandler([new Response(200, [], '{}')]));
		$handlerStack->push(Middleware::history($history));

		$client->request('POST', '/crm/v3/objects/contacts', [], null, true);

		self::assertCount(1, $history);

		/** @var RequestInterface $request */
		$request = $history[0]['request'];
		self::assertSame(
			'https://proxy.example.com/hubspot-gateway/crm/v3/objects/contacts',
			(string)$request->getUri()
		);
		self::assertSame('test-api-key', $request->getHeaderLine('x-api-key'));
		self::assertFalse($request->hasHeader('Authorization'));
	}
}
