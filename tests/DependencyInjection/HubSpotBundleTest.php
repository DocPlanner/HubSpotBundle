<?php

namespace DocPlanner\Bundle\HubSpotBundle\Tests\DependencyInjection;

use DocPlanner\Bundle\HubSpotBundle\HubSpotBundle;
use DocPlanner\Bundle\HubSpotBundle\Services\ProxyAwareFactory;
use DocPlanner\Bundle\HubSpotBundle\Services\ProxyClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;

final class HubSpotBundleTest extends TestCase
{
	public function testProxyConfiguredServiceBuilds(): void
	{
		$bundle    = new HubSpotBundle();
		$container = new ContainerBuilder();
		$container->registerExtension($bundle->getContainerExtension());
		$container->loadFromExtension('hub_spot', [
			'key'   => 'legacy-key',
			'proxy' => [
				'custom_url'     => 'https://proxy.example.com/hubspot-gateway',
				'custom_headers' => ['x-api-key' => 'test-api-key', 'Authorization' => 'Bearer token'],
			],
		]);
		$bundle->build($container);
		$container->addCompilerPass(new PublicHubSpotPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION);
		$container->compile();

		self::assertStringContainsString('class HubSpotTestContainer', (new PhpDumper($container))->dump(['class' => 'HubSpotTestContainer']));

		$factory = $container->get('hubspot');
		self::assertInstanceOf(ProxyAwareFactory::class, $factory);
		$client = new \ReflectionProperty(ProxyAwareFactory::class, 'client');
		$client->setAccessible(true);
		self::assertInstanceOf(ProxyClient::class, $client->getValue($factory));
	}
}

final class PublicHubSpotPass implements CompilerPassInterface
{
	public function process(ContainerBuilder $container)
	{
		$container->getDefinition('hubspot')->setPublic(true);
	}
}
