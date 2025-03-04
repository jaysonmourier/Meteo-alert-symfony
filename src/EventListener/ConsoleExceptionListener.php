<?php

declare(strict_types=1);

namespace App\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConsoleExceptionListener
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function onConsoleError(ConsoleErrorEvent $event): void
    {
        $exception = $event->getError();
        $command = $event->getCommand();
        $io = new SymfonyStyle($event->getInput(), new ConsoleOutput());

        $this->logger->error(
            sprintf("Error command '%s': %s", $command ? $command->getName() : 'unknown', $exception->getMessage())
        );

        $io->error(sprintf(
            'Une erreur est survenue dans la commande "%s": %s',
            $command ? $command->getName() : 'inconnue',
            $exception->getMessage()
        ));

        $event->setExitCode(1);
    }
}
