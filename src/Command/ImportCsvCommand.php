<?php

declare(strict_types=1);

namespace App\Command;

use Exception;
use App\Dto\CsvParseResult;
use Psr\Log\LoggerInterface;
use App\Service\CsvParserService;
use App\Service\DataValidatorService;
use App\Repository\DestinataireRepository;
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

    private int $rowNumber;
    private int $successCount;
    private int $errorsCount;
    private int $insertedRows;

    public function __construct(
        private LoggerInterface $logger,
        private DestinataireRepository $destinataireRepository,
        private DataValidatorService $dataValidatorService,
        private CsvParserService $csvParserService
    ) {
        parent::__construct();
    }

    protected function configure(): void {
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
    protected function execute(InputInterface $inputInterface, OutputInterface $outputInterface): int {
        try {
            // get file path
            $filePath = $inputInterface->getArgument(self::ARG_FILE_PATH);

            $csvParseResult = $this->csvParserService->parse($filePath);
            if (!empty($csvParseResult->data)) {
                $this->insertedRows = $this->destinataireRepository->insertBulk($$csvParseResult->data);
            }

            // print report
            $this->printReport($outputInterface);

            return Command::SUCCESS;
        } catch (Exception $e) {
            $outputInterface->writeln('<error>' . $e->getMessage() . '</error>');
            $this->logger->error("Exception: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * La méthode printReport permet de générer un rapport pour informer l'utilisateur
     * sur la finalité de l'exécution de la commande app:csv-import.
     * 
     * Elle affiche:
     *  - le nombre total de lignes lues
     *  - Le nombre total de lignes correctement parsées
     *  - Le nombre total d'erreurs
     * 
     * @param \Symfony\Component\Console\Output\OutputInterface $outputInterface
     * @return void
     */
    private function printReport(OutputInterface $outputInterface): void {
        $outputInterface->writeln("\n<info>CSV IMPORT REPORT</info>");
        $outputInterface->writeln("<comment>===================</comment>\n");

        $outputInterface->writeln("<info>Total rows:</info>  " . $this->rowNumber);
        $outputInterface->writeln("<info>✔ Success Count:</info>  " . $this->successCount);
        $outputInterface->writeln("<info>✔ Inserted Rows:</info>  " . $this->insertedRows);
        $outputInterface->writeln("<error>✖ Error Count:</error>  " . $this->errorsCount);

        if ($this->errorsCount > 0) {
            $outputInterface->writeln("\n<comment>⚠ Some rows were skipped due to errors.</comment>");
        }

        $outputInterface->writeln("\n<comment>===================</comment>\n");
    }
}
