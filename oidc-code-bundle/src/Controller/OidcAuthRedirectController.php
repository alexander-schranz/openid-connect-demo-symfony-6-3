<?php

namespace Sulu\OidcCode\Controller;

use Sulu\OidcCode\Security\AccessToken\OidcCodeQueryAccessTokenExtractor;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

class OidcAuthRedirectController
{
    public function __construct(
        private string $baseUri,
        private string $clientId,
        private string $redirectUri,
        private string $responseType = 'code',
        private string $scope = 'openid',
        private bool $hasCodeChallenge = false,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $state = Uuid::v4()->__toString();
        $request->getSession()->set(OidcCodeQueryAccessTokenExtractor::OIDC_STATE_PARAMETER, $state);

        $nonce = Uuid::v4()->__toString();

        $query = [
            'response_type' => $this->responseType,
            'scope' => $this->scope,
            'client_id' => $this->clientId,
            'state' => $state,
            'nonce' => $nonce,
            'redirect_uri' => $this->redirectUri,
        ];

        if ($this->hasCodeChallenge) {
            $codeVerifier = base64_encode(random_bytes(32));
            $codeChallenge = base64_encode(hash('sha256', $codeVerifier, true));
            $codeChallenge = rtrim($codeChallenge, '=');
            $codeChallenge = urlencode($codeChallenge);

            $request->getSession()->set(OidcCodeQueryAccessTokenExtractor::OIDC_CODE_VERIFIER_PARAMETER, $codeVerifier);

            $query['code_challenge'] = $codeChallenge;
            $query['code_challenge_method'] = 'S256';

            // TODO think the code challenge maybe need also be validated somewhere
        }

        $url = $this->baseUri . 'auth?' . http_build_query($query);

        return new RedirectResponse($url);
    }
}
