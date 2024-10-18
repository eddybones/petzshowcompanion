<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController {
    protected const RESET_EMAIL_SUBJECT = 'Petz Show Companion - Reset Password';

    #[Route('/login', name: 'app_login')]
    public function index(AuthenticationUtils $authUtils): Response {
        return $this->render('login/index.html.twig', [
            'last_username' => $authUtils->getLastUsername(),
            'error'         => $authUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void {
        // Handled by security configuration
    }

    #[Route('/forgot_password', name: 'forgot_password', methods: ['GET'])]
    public function forgotPassword(): Response {
        return $this->render('login/forgot_password.html.twig');
    }

    #[Route('/forgot_password', name: 'send_password_reset', methods: ['POST'])]
    public function sendPasswordReset(Request $request, UserRepository $userRepo, MailerInterface $mailer): Response {
        $email = $request->get('email');
        $user = $userRepo->findOneBy(['email' => $email]);
        if($user) {
            $user->setResetToken(hash($_ENV['SMALL_HASH'], $user->getId() . time()));
            $user->setResetInitiated(new DateTime());
            $userRepo->save($user, true);

            $url = $_ENV['BASE_URL'] . '/password_reset/' . urlencode($user->getResetToken());

            $html = <<<HTML
                <p>Click on the link below to reset your password:<br><a href="$url">Reset Password</a></p>
                <p>Note: This is a one-time reset link and expires 1 hour after the time it was sent.</p>
                <p>If you received this email in error, you may safely disregard it.</p>
            HTML;

            $email = (new Email())
                ->to($user->getEmail())
                ->subject(self::RESET_EMAIL_SUBJECT)
                ->html($html);

            $mailer->send($email);
        }

        return $this->render('login/password_reset_sent.html.twig');
    }

    public function tokenIsValid(string $token, ?User $user): bool {
        if($user && $user->getResetToken() === $token && $user->getResetInitiated()) {
            $diff = $user->getResetInitiated()->diff(new DateTime());
            // Need to make sure hours is less than 1 and minutes (i) converted to seconds + remaining seconds is less than total seconds in 1 hour
            if($diff->h == 0 && (($diff->i * 60) + $diff->s <= 60 * 60)) {
                return true;
            }
        }
        return false;
    }

    #[Route('/password_reset/{token}', name: 'password_reset', methods: ['GET'])]
    public function passwordReset(string $token, UserRepository $userRepo): Response {
        $user = $userRepo->findOneBy(['resetToken' => $token]);
        if($this->tokenIsValid($token, $user)) {
            return $this->render('login/password_reset_form.html.twig', [
                'token' => $token,
                'password_no_match' => false,
            ]);
        }
        // If user clicks link a second time, user will come back null
        if($user) {
            $user->setResetToken(null);
            $user->setResetInitiated(null);
            $userRepo->save($user, true);
        }
        return $this->render('login/password_reset_invalid.html.twig');
    }

    #[Route('/password_reset', name: 'password_reset_action', methods: ['POST'])]
    public function passwordResetAction(Request $request, UserRepository $userRepo, UserPasswordHasherInterface $hasher): Response {
        $token = $request->get('token');
        $user = $userRepo->findOneBy(['resetToken' => $token]);
        if($this->tokenIsValid($token, $user)) {
            $password = $request->get('newpassword');
            $repeat = $request->get('repeatpassword');
            if($repeat !== $password) {
                return $this->render('login/password_reset_form.html.twig', [
                    'token'          => $token,
                    'password_no_match' => true,
                ]);
            }
            $user->setPassword($hasher->hashPassword($user, $password));
            $user->setResetToken(null);
            $user->setResetInitiated(null);
            $userRepo->save($user, true);
        }
        return $this->render('login/password_reset_complete.html.twig');
    }
}