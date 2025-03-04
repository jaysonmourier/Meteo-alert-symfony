<?php

declare(strict_types=1);

namespace App\Command;

use Psr\Log\LoggerInterface;
use App\Service\CsvParserService;
use App\Service\DestinataireService;
use App\Service\ImportReportService;
use App\Service\DataValidatorService;
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
        private DestinataireService $destinataireService,
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
     *  - Elle récupère le chemin relatif au fichier
     *  - Elle parse le fichier avec la méthode parse du service App\Service\CsvParserService
     *  - Si des données sont retournées, elle les sauvegarde en base de données via le service App\Service\DestinataireService
     *  - Elle affiche le rapport avec le service App\Service\ImportReportService
     *
     * En cas de succès, la méthode retourne Command::SUCCESS. En cas d'erreur, Command::FAILURE est retourné.
     * 
     * Les exceptions sont gérées par App\EventListener\ConsoleExceptionListener
     *
     * @param \Symfony\Component\Console\Input\InputInterface $inputInterface
     * @param \Symfony\Component\Console\Output\OutputInterface $outputInterface
     * @return int
     */
    protected function execute(InputInterface $inputInterface, OutputInterface $outputInterface): int
    {
        $this->logger->info("START app:csv-import command");

        // get file path
        $filePath = $inputInterface->getArgument(self::ARG_FILE_PATH);
        
        // parse csv
        $csvParseResult = $this->csvParserService->parse($filePath);
        if (!empty($csvParseResult->data)) {
            $insertedRows = $this->destinataireService->persistDestinataires($csvParseResult->data);
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
    }
}
