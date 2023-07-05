<?php

namespace Sulu\OidcCode;

use Sulu\OidcCode\Security\AccessToken\OidcCodeHandlerFactory;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AccessTokenFactory;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class SuluOidcCodeBundle extends AbstractBundle
{
    protected string $extensionAlias = 'sulu_oidc_token';

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');
    }
    /**
     * @return void
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        /** @var SecurityExtension $extension */
        $extension = $container->getExtension('security');

        $extension->addAuthenticatorFactory(new AccessTokenFactory([
            new OidcCodeHandlerFactory(),
        ]));
    }
}
