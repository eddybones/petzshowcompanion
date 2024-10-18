<?php

namespace App\Twig;

use App\Enum\PetType;
use App\Enum\ShowType;
use App\Service\ShowTitleService;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension {
    private Filesystem $filesystem;

    public function __construct(Filesystem $filesystem) {
        $this->filesystem = $filesystem;
    }

    public function getFunctions(): array {
        return [
            new TwigFunction('picExists', [$this, 'picExists']),
            new TwigFunction('getShowTitle', [$this, 'getShowTitle']),
            new TwigFunction('rando', [$this, 'rando']),
            new TwigFunction('getEnv', [$this, 'getEnv']),
        ];
    }

    public function getEnv(string $name): string {
        return $_ENV[$name];
    }

    public function picExists(?string $fileName): bool {
        if($fileName === null) {
            return false;
        }
        return $this->filesystem->exists($_ENV['PIC_PATH'] . DIRECTORY_SEPARATOR . $fileName);
    }

    public function getShowTitle(ShowType $type, PetType $petType, int $points): string {
        return ShowTitleService::getTitle($type, $petType, $points);
    }

    public function rando(): string {
        return bin2hex(random_bytes(4));
    }

    public function getFilters() {
        return [
            new TwigFilter('camelSpace', [$this, 'camelSpace']),
        ];
    }

    public function camelSpace(?string $phrase): ?string {
        if($phrase === null) {
            return null;
        }

        // These user negative lookbehind (thank you, internet) to check that a space is not found.
        // It then matches a capital + series of lowercase
        // or a capital + series of capitcals
        $matchFilter = [
            '/(?<!\ )[A-Z][a-z]+/',
            '/(?<!\ )[A-Z][A-Z]+/',
        ];

        return trim(preg_replace($matchFilter, ' $0', trim($phrase)));
    }
}