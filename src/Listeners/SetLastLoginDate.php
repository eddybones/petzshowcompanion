<?php

namespace App\Listeners;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class SetLastLoginDate implements EventSubscriberInterface {
    private EntityManagerInterface $em;
    private LoggerInterface $logger;
    
    public function __construct(EntityManagerInterface $em, LoggerInterface $logger) {
        $this->em = $em;
        $this->logger = $logger;
    }
    
    public static function getSubscribedEvents() {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event) {
        try {
            $user = $event->getUser();
            $user->setLastLogin(new DateTime());
            $this->em->persist($user);
            $this->em->flush();
        } catch(Exception $e) {
            $this->logger->log('Error setting last login.', $e);
        }
    }
}