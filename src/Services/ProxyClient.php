<?php

namespace DocPlanner\Bundle\HubSpotBundle\Services;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use SevenShores\Hubspot\Http\Client as HubspotClient;

class ProxyClient extends HubspotClient
{
    /** @inheritDoc */
    public function __construct($config = [], $client = null, $clientOptions = [], $wrapResponse = true)
    {
        if (null !== $this->client) {
            return;
        }

        parent::__construct($config, $client, $clientOptions, $wrapResponse);

        $proxyConfig = $config['proxy'];

        $customUrl = $proxyConfig['custom_url'];
        $customHeaders = $proxyConfig['custom_headers'];

        $handlerStack = HandlerStack::create();
        $handlerStack->push(Middleware::mapRequest(function (RequestInterface $request) use (
            $customUrl,
            $customHeaders
        ) {

            $parsedUrl = parse_url($request->getUri());
            $uri = vsprintf('%s%s?%s', [
                $customUrl,
                $parsedUrl['path'],
                $parsedUrl['query'] ?? '',
            ]);

            $request = $request->withUri(new Uri($uri));

            foreach ($customHeaders as $customHeaderName => $customHeaderValue) {
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
}
