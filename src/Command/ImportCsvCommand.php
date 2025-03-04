<?php

declare(strict_types=1);

namespace App\Command;

use Exception;
use Psr\Log\LoggerInterface;
use App\Service\CsvParserService;
use App\Service\DataValidatorService;
use App\Repository\DestinataireRepository;
use App\Service\ImportReportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:csv-import',
    description: 'Import a CSV file, parse its content, and store valid (INSEE, PHONE) records in the database.',
    hidden: false
)]
class ImportCsvCommand extends Command
{
    public const ARG_FILE_PATH = "file";

    public function __construct(
        private LoggerInterface $logger,
        private DestinataireRepository $destinataireRepository,
        private DataValidatorService $dataValidatorService,
        private CsvParserService $csvParserService,
        private ImportReportService $importReportService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(self::ARG_FILE_PATH, InputArgument::REQUIRED, 'Path to the CSV file');
    }

    /**
     * Méthode principale de la commande app:csv-import, elle fonctionne comme tel:
     *  - Elle récupère le chemin du fichier avec la méthode getArgument
     *  - Elle vérifie l'existence du fichier
     *  - Elle parse le fichier CSV via la méthode parseCsvRows
     *  - Elle insert les données en base via le repository DestinataireRepository
     *  - Elle affiche le rapport pour informer l'utilisateur du nombre de succès et d'erreurs
     *
     * En cas de succès, la méthode retourne Command::SUCCESS. En cas d'erreur, Command::FAILURE est retourné.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $inputInterface
     * @param \Symfony\Component\Console\Output\OutputInterface $outputInterface
     * @return int
     */
    protected function execute(InputInterface $inputInterface, OutputInterface $outputInterface): int
    {
        try {
            // get file path
            $filePath = $inputInterface->getArgument(self::ARG_FILE_PATH);

            $this->logger->info("Start to parse: " . $filePath);

            // parse csv
            $csvParseResult = $this->csvParserService->parse($filePath);
            if (!empty($csvParseResult->data)) {
                $insertedRows = $this->destinataireRepository->insertBulk($csvParseResult->data);
            }

            // generate report
            $this->importReportService->generate(
                $outputInterface,
                $csvParseResult->totalRows,
                $csvParseResult->totalValidRows,
                $insertedRows,
                $csvParseResult->totalErrorRows,
            );

            return Command::SUCCESS;
        } catch (Exception $e) {
            $outputInterface->writeln('<error>' . $e->getMessage() . '</error>');
            $this->logger->error("(" . $filePath . ") Exception: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
