<?php

namespace App\Service;

use App\Controller\EmailVerificationException;
use App\Entity\User;
use App\Entity\UserOptions;
use App\Entity\UserProfile;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Throwable;

class RegistrationService {
    private UserPasswordHasherInterface $hasher;
    private LoggerInterface $logger;
    private UserRepository $userRepo;
    private EntityManagerInterface $em;

    public function __construct(UserPasswordHasherInterface $hasher, LoggerInterface $logger, UserRepository $userRepo, EntityManagerInterface $em) {
        $this->hasher = $hasher;
        $this->logger = $logger;
        $this->userRepo = $userRepo;
        $this->em = $em;
    }

    /**
     * @param string $email
     * @param string $password
     *
     * @return User|null
     */
    public function save(string $email, string $password): ?User {
        try {
            $user = new User($email);
            $passwordHash = $this->hasher->hashPassword($user, $password);
            $token = bin2hex(random_bytes(4));
            $user->setPassword($passwordHash)
                ->setVerified(false)
                ->setVerificationToken($token);
            $user->setHash(hash($_ENV['SMALL_HASH'], $user->getEmail()));
            // TODO: Fix this. I don't think you should need to call persist/flush multiple times. I struggled with
            // the mapping and from User to UserOptions and making it cascade persist, so I gave up. I could be wrong.
            $this->em->persist($user);
            $this->em->flush();
            $userOption = new UserOptions($user->getId(), $user, false);
            $this->em->persist($userOption);
            $this->em->flush();
            $userProfile = new UserProfile($user, '', '', '', '', true, '', 0, 0, false);
            $this->em->persist($userProfile);
            $this->em->flush();
            return $user;
        } catch(Throwable $e) {
            $this->logger->error('Could not save user entity', [
                'exception' => $e,
            ]);
        }
        return null;
    }

    public function sendVerificationEmail(MailerInterface $mailer, User $user): void {
        try {
            $mailer->send((new Email())
                ->to($user->getEmail())
                ->subject('Petz Show Companion - Verify your email address')
                ->text('Please click the following link to verify your email address: ' . $_ENV['BASE_URL'] . '/verify/' . base64_encode($user->getVerificationToken() . '|' . $user->getId()))
            );
        } catch(Throwable $e) {
            $this->logger->error('Could not send verification email', [
                'exception' => $e,
            ]);
        }

        $this->logger->info('Sent verification email', [
            'email' => $user->getEmail(),
            'token' => $user->getVerificationToken(),
        ]);
    }

    public function resendVerificationEmail(MailerInterface $mailer, string $email): void {
        $user = $this->userRepo->findOneBy(['email' => $email]);
        if(!$user) {
            $this->logger->warning('No user found for email verification resend', [
                'email' => $email,
            ]);
            return;
        }
        $this->sendVerificationEmail($mailer, $user);
    }

    public function verifyEmail(int $userId, string $token): void {
        $user = $this->userRepo->findOneBy(['id' => $userId, 'verificationToken' => $token]);
        if(!$user) {
            $this->logger->warning('Could not verify email.', [
                'userId' => $userId,
                'token' => $token,
            ]);
            throw new EmailVerificationException('Could not verify email.');
        }
        $user->setVerified(true);
        $user->setVerificationToken(null);
        $this->em->flush();
    }
}