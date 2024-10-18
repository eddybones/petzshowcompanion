<?php

namespace App\Command;

use App\Repository\PetRepository;
use App\Service\PetService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:rehash')]
class Rehash extends Command
{
    private PetRepository $petRepository;
    private PetService $petService;

    public function __construct(PetRepository $petRepository, PetService $petService, string $name = null) {
        parent::__construct($name);
        $this->petRepository = $petRepository;
        $this->petService = $petService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach($this->petRepository->findAll() as $pet) {
            $this->petService->setHash($pet);
        }
        return Command::SUCCESS;
    }
}