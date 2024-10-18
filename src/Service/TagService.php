<?php

namespace App\Service;

use App\Controller\TagException;
use App\Entity\Tag;
use App\Repository\TagRepository;
use Symfony\Component\Security\Core\User\UserInterface;

class TagService {
    private TagRepository $tagRepo;

    public function __construct(TagRepository $tagRepo) {
        $this->tagRepo = $tagRepo;
    }

    public function getTagIfOwnedByUser(UserInterface $user, string $hash): Tag {
        $tag = $this->tagRepo->findOneBy(['hash' => $hash, 'user' => $user]);

        if(!$tag) {
            throw new TagException('This tag does not exist or does not belong to you...');
        }

        return $tag;
    }
}