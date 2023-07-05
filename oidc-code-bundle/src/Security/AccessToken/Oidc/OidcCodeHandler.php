<?php

namespace Sulu\OidcCode\Security\AccessToken\Oidc;

use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\AccessToken\Oidc\Exception\MissingClaimException;
use Symfony\Component\Security\Http\AccessToken\Oidc\OidcUserInfoTokenHandler;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class OidcCodeHandler implements AccessTokenHandlerInterface
{
    private OidcUserInfoTokenHandler $oidcUserInfoTokenHandler;

    public function __construct(
        private HttpClientInterface $client,
        string $baseUri,
        #[\SensitiveParameter] private string $clientId,
        #[\SensitiveParameter] private string $clientSecret,
        private string $redirectUri,
        private ?LoggerInterface $logger = null,
        string $claim = 'sub',
    ) {
        $this->oidcUserInfoTokenHandler = new OidcUserInfoTokenHandler(
            $client->withOptions([
                'base_uri' => $baseUri . 'userinfo',
            ]),
            $logger,
            $claim
        );
    }

    public function getUserBadgeFrom(#[\SensitiveParameter] string $accessToken): UserBadge
    {
        try {
            $data = $this->client->request(
                'POST',
                'token',
                [
                    'auth_basic' => $this->clientId . ':' . $this->clientSecret,
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ],
                    'body' => 'grant_type=authorization_code&code=' . $accessToken . '&redirect_uri=' . $this->redirectUri,
                ],
            )->toArray();

            $accessToken = $data['access_token'] ?? null;

            if (!$accessToken) {
                throw new MissingClaimException(sprintf('The "access_token" not found on OIDC server response.'));
            }

            $userBadge = $this->oidcUserInfoTokenHandler->getUserBadgeFrom($accessToken);

            return $userBadge;
        } catch (BadCredentialsException $e) {
            // avoid double logging for BadCredentialsException

            throw $e;
        } catch (\Exception $e) {
            $this->logger?->error('An error occurred on OIDC server.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new BadCredentialsException('Invalid code.', $e->getCode(), $e);
        }
    }
}
