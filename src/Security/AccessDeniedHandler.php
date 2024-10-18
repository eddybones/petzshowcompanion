<?php

namespace App\Security;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

class AccessDeniedHandler implements AccessDeniedHandlerInterface {
    private Security $security;

    public function __construct(Security $security) {
        $this->security = $security;
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?Response {
        if(str_contains($accessDeniedException->getMessage(), 'ROLE_EMAIL_VERIFIED')) {
            $request->getSession()->set('verify_email', $this->security->getUser()->getEmail());
            return new RedirectResponse('/verify_email');
        }

        return new Response('Access denied', Response::HTTP_FORBIDDEN);
    }
}