<?php

namespace App\Controller;

use App\Service\RegistrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController {
    #[Route('/register', name: 'register', methods: ['GET'])]
    public function register(): Response {
        return $this->render('registration/index.html.twig', [
            'enabled' => (bool)$_ENV['REGISTRATION_ENABLED'],
        ]);
    }

    #[Route('/register', name: 'register_action', methods: ['POST'])]
    public function registerAction(Request $request, MailerInterface $mailer, Security $security, RegistrationService $service): Response {
        $email = trim($request->get('email') ?? '');
        $password = (string)$request->get('password');

        if(!$_ENV['REGISTRATION_ENABLED'] || (!strlen($email) || !strlen($password))) {
            return $this->redirectToRoute('register', [
                'missing_data' => true,
                'email'        => $email,
                'password'     => $password,
                'enabled'      => (bool)$_ENV['REGISTRATION_ENABLED'],
            ]);
        }

        $user = $service->save($email, $password);
        if($user) {
            $service->sendVerificationEmail($mailer, $user);
            $request->getSession()->set('verify_email', $user->getEmail());
            return $this->render('registration/register_success.html.twig', [
                'email' => $user->getEmail(),
            ]);
        }

        // TODO: Otherwise show error page...
    }

    #[Route('/verify_email', name: 'verify_email', methods: ['GET'])]
    public function verifyEmailReminder(Request $request): Response {
        return $this->render('registration/verify_reminder.html.twig', [
            'email' => $request->getSession()->get('verify_email'),
        ]);
    }

    #[Route('/verify/{token}', name: 'verify', methods: ['GET'])]
    public function verify(Request $request, RegistrationService $service, string $token): Response {
        $response = fn($success) => $this->render('registration/verification_result.html.twig', [
            'success' => $success,
        ]);

        $decoded = base64_decode($token);
        if(!$decoded) {
            return $response(false);
        }

        $parts = explode('|', $decoded);
        if(count($parts) != 2) {
            return $response(false);
        }

        $verificationToken = $parts[0];
        $userId = $parts[1];

        try {
            $service->verifyEmail($userId, $verificationToken);
            $request->getSession()->remove('verify_email');
        } catch(EmailVerificationException $e) {
            return $response(false);
        }

        return $response(true);
    }

    #[Route('/resend_verification', name: 'resend_verification', methods: ['GET'])]
    public function resendVerification(RegistrationService $service, MailerInterface $mailer): Response {
        $email = $this->getUser()->getEmail();
        $service->resendVerificationEmail($mailer, $email);
        return $this->render('registration/resend_verification.html.twig', [
            'email' => $email,
        ]);
    }
}
