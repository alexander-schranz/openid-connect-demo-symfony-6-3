<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Sulu\OidcCode\Security\AccessToken\Oidc\OidcCodeHandler;
use Sulu\OidcCode\Security\AccessToken\OidcCodeQueryAccessTokenExtractor;
use Sulu\OidcCode\Controller\OidcAuthRedirectController;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/*
 * @internal
 */
return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('security.access_token_handler.oidc_code.http_client', HttpClientInterface::class)
            ->abstract()
            ->factory([service('http_client'), 'withOptions'])
            ->args([abstract_arg('http client options')])

        ->set('security.access_token_handler.oidc_code', OidcCodeHandler::class)
            ->abstract()
            ->args([
                abstract_arg('http client'),
                abstract_arg('baseUri'),
                abstract_arg('clientId'),
                abstract_arg('clientSecret'),
                abstract_arg('redirectUri'),
                service('logger')->nullOnInvalid(),
                abstract_arg('claim'),
            ])

        ->set('security.controller.oidc_auth_redirect_controller', OidcAuthRedirectController::class)
            ->abstract()
            ->public()
            ->args([
                abstract_arg('baseUri'),
                abstract_arg('clientId'),
                abstract_arg('redirectUri'),
                'code',
                'openid',
                false,
            ])

        ->set('security.access_token_handler.oidc_code.token_extractor', OidcCodeQueryAccessTokenExtractor::class)
    ;
};
