<?php

namespace Sulu\OidcCode\Security\AccessToken;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\AccessToken\AccessTokenExtractorInterface;

final class OidcCodeQueryAccessTokenExtractor implements AccessTokenExtractorInterface
{
    final public const OIDC_STATE_PARAMETER = '_oidc_state';

    final public const OIDC_CODE_VERIFIER_PARAMETER = '_oidc_code_verifier';

    public function extractAccessToken(Request $request): ?string
    {
        $parameter = $request->query->get('code');

        $code = \is_string($parameter) ? $parameter : null;

        if ($code === null) {
            return null;
        }

        $state = $request->query->get('state');
        $sessionState = $request->getSession()->get('_oidc_state');

        if (!$state || $state !== $sessionState) {
            return null;
        }

        return $code;
    }
}
