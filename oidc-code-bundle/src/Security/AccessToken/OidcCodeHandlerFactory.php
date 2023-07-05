<?php

namespace Sulu\OidcCode\Security\AccessToken;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\AccessToken\TokenHandlerFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OidcCodeHandlerFactory implements TokenHandlerFactoryInterface
{
    public function create(ContainerBuilder $container, string $id, array|string $config): void
    {
        $clientDefinition = (new ChildDefinition('security.access_token_handler.oidc_code.http_client'))
            ->replaceArgument(0, ['base_uri' => $config['base_uri']]);

        if (isset($config['client'])) {
            $clientDefinition->setFactory([new Reference($config['client']), 'withOptions']);
        } elseif (!ContainerBuilder::willBeAvailable('symfony/http-client', HttpClientInterface::class, ['symfony/security-bundle'])) {
            throw new LogicException('You cannot use the "oidc_token" token handler since the HttpClient component is not installed. Try running "composer require symfony/http-client".');
        }

        $container->setDefinition($id, new ChildDefinition('security.access_token_handler.oidc_code'))
            ->replaceArgument(0, $clientDefinition)
            ->replaceArgument(1, $config['base_uri'])
            ->replaceArgument(2, $config['client_id'])
            ->replaceArgument(3, $config['client_secret'])
            ->replaceArgument(4, $config['redirect_uri'])
            // logger
            ->replaceArgument(6, $config['claim']);

        $container->setDefinition($id . '.redirect_controller', new ChildDefinition('security.controller.oidc_auth_redirect_controller'))
            ->replaceArgument(0, $config['base_uri'])
            ->replaceArgument(1, $config['client_id'])
            ->replaceArgument(2, $config['redirect_uri'])
            ->replaceArgument(3, 'code')
            ->replaceArgument(4, $config['scope'])
            ->replaceArgument(5, false);
    }

    public function getKey(): string
    {
        return 'oidc_code';
    }

    public function addConfiguration(NodeBuilder $node): void
    {
        $node
            ->arrayNode($this->getKey())
                ->fixXmlConfig($this->getKey())
                ->beforeNormalization()
                    ->ifString()
                    ->then(static fn ($v) => ['claim' => 'sub', 'base_uri' => $v])
                ->end()
                ->children()
                    ->scalarNode('base_uri')
                        ->info('Base URI of the endpoint on the OIDC server e.g.: "http://127.0.0.1:8080/realms/symfony/protocol/openid-connect/".')
                        ->isRequired()
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('claim')
                        ->info('Claim which contains the user identifier (e.g. sub, email, etc.).')
                        ->defaultValue('sub')
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('client_id')
                        ->info('The client id of the openid client.')
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('client_secret')
                        ->info('The client secret of the openid client.')
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('redirect_uri')
                        ->info('The redirect_uri of the openid client.')
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('scope')
                        ->info('The scope for the openid client.')
                        ->defaultValue('openid')
                        ->cannotBeEmpty()
                    ->end()
                    ->booleanNode('code_challenge')
                        ->info('The client requires a code challenge.')
                        ->defaultValue(false)
                    ->end()
                    ->scalarNode('client')
                        ->info('HttpClient service id to use to call the OIDC server.')
                    ->end()
                ->end()
            ->end()
        ;
    }
}
