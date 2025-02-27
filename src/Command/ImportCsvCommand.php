<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:csv-import',
    description: 'Import a CSV',
    hidden: false
)]
class ImportCsvCommand extends Command {
    protected function configure(): void {
        $this->addArgument('file', InputArgument::REQUIRED, 'Path to the CSV file');
    }

    protected function execute(InputInterface $inputInterface, OutputInterface $outputInterface): int {
        // get the file path
        $filePath = $inputInterface->getArgument('file');

        // check if the file exists
        if (!file_exists($filePath)) {
            $outputInterface->writeln('<error>File not found: ' . $filePath . '</error>');
            return Command::FAILURE;
        }
        
        // open file
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            $outputInterface->writeln('<error>Cannot open file: ' . $filePath . '</error>');
            return Command::FAILURE;
        }

        $rowNumber = 0;
        while (($row = fgetcsv($handle, 256, ',')) !== false) {
            $rowNumber++;

            if (count($row) < 2) {
                $outputInterface->writeln('<comment>Skipping invalid row #' . $rowNumber . '</comment>');
                continue;
            }

            [$insee, $phone] = $row;

            if (!$this->isValidInsee($insee) || !$this->isValidPhone($phone)) {
                $outputInterface->writeln('<comment>Invalid data at row #' . $rowNumber . '</comment>');
                continue;
            }

            $outputInterface->writeln("<info>Row #" . $rowNumber . ": INSEE=" . $insee . ", Phone:" . $phone . "</info>");
        }

        // close file
        fclose($handle);
        return Command::SUCCESS;
    }

    private function isValidInsee(string $insee): bool
    {
        return preg_match('/^\d{5}$/', $insee);
    }

    private function isValidPhone(string $phone): bool
    {
        return preg_match('/^\+?\d{10,15}$/', $phone);
    }
}
