<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Uid\Uuid;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        $codeChallenge = $request->getSession()->get('code_challenge');
        $uuid = $request->getSession()->get('uuid');

        if (!$codeChallenge) {
            $codeVerifier = base64_encode(random_bytes(32));
            $codeChallenge = base64_encode(hash('sha256', $codeVerifier, true));
            $codeChallenge = rtrim($codeChallenge, '=');
            $codeChallenge = urlencode($codeChallenge);

            $request->getSession()->set('code_verifier', $codeVerifier);
            $request->getSession()->set('code_challenge', $codeChallenge);
        }

        if (!$uuid) {
            $uuid = Uuid::v4()->__toString();
            $request->getSession()->set('uuid', $uuid);
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'state' => $uuid,
            'nonce' => $uuid,
            'code_challenge' => $codeChallenge,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
