<?php

declare(strict_types=1);

namespace DocPlanner\Bundle\HubSpotBundle\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use SevenShores\Hubspot\Http\Client as HubspotClient;

class ProxyClient extends HubspotClient
{
    /**
     * Make it, baby.
     *
     * @param  array $config Configuration array
     * @param  GuzzleClient $client The Http Client (Defaults to Guzzle)
     * @param array $clientOptions options to be passed to Guzzle upon each request
     * @param bool $wrapResponse wrap request response in own Response object
     */
    public function __construct($config = [], $client = null, $clientOptions = [], $wrapResponse = true)
    {
        $customUrl    = $config['custom_url'];
        $customHeaders    = $config['custom_headers'];

        $handlerStack = HandlerStack::create();
        $handlerStack->push(Middleware::mapRequest(function (RequestInterface $request) use (
            $awsProxyUrl,
            $awsAccessKey,
            $awsSecretKey,
            $awsApiToken,
            $awsServiceName,
            $awsRegion
        ) {
            $signature      = new SignatureV4($awsServiceName, $awsRegion);
            $awsCredentials = new Credentials($awsAccessKey, $awsSecretKey);
            $uri            = str_replace('https://api.hubapi.com', $awsProxyUrl, $request->getUri());

            $request = $request
                ->withHeader('x-api-key', $awsApiToken)
                ->withUri(new Uri($uri));
            $request = $signature->signRequest($request, $awsCredentials);

            return $request;
        }));

        $newClient = new Client([
            'handler' => $handlerStack,
        ]);

        return new self($config, $newClient, $clientOptions, $wrapResponse);
    }
}
