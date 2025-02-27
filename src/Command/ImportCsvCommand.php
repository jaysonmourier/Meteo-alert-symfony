<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
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
class ImportCsvCommand extends Command 
{
    public const INSEE_INDEX = "insee";
    public const TELEPHONE_INDEX = "telephone";

    private int $rowNumber;
    private int $successCount;
    private int $errorCount;

    public function __construct(
        private Connection $connection,
        private LoggerInterface $loggerInterface
    ) {
        parent::__construct();
        $this->rowNumber = 0;
        $this->successCount = 0;
        $this->errorCount = 0;
    }

    protected function configure(): void {
        $this->addArgument('file', InputArgument::REQUIRED, 'Path to the CSV file');
    }

    /**
     * Fonction principale de la commande, elle permet d'appeler les fonctions nécessaires
     * au bon fonctionnement de la commande
     * 
     * @param \Symfony\Component\Console\Input\InputInterface $inputInterface
     * @param \Symfony\Component\Console\Output\OutputInterface $outputInterface
     * @return int
     */
    protected function execute(InputInterface $inputInterface, OutputInterface $outputInterface): int {
        try {
            // get file path
            $filePath = $inputInterface->getArgument('file');

            // check if file exists
            if (!file_exists($filePath)) {
                $outputInterface->writeln('<error>File not found: ' . $filePath . '</error>');
                return Command::FAILURE;
            }

            // parse CSV
            $batchData = $this->parseCsvRows($filePath);
            
            // persist data to database
            $this->batchInsert($batchData);

            // print report
            $this->printReport($outputInterface);

            return Command::SUCCESS;
        } catch (\RuntimeException $e) {
            $outputInterface->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }

    /**
     * Vérifie que le numéro INSEE est bien valide
     * Si il est valide, isValidInsee retourne true sinon, false
     * 
     * @param string $insee
     * @return bool|int
     */
    private function isValidInsee(string $insee): bool
    {
        return $insee !== null && preg_match('/^\d{5}$/', $insee);
    }

    /**
     * Vérifie que le numéro de téléphone soit bien valide
     * Si il est valide, isValidPhone retourne true sinon, false
     * 
     * @param string $phone
     * @return bool|int
     */
    private function isValidPhone(string $phone): bool
    {
        return $phone !== null && preg_match('/^\+?\d{10,15}$/', $phone);
    }

    /**
     * Cette fonction permet d'ouvrir et de parcourir un CSV 
     * afin de créer un batch de données valides.
     * 
     * Nous considérons valide un couple (INSEE, TELEPHONE) 
     * si les fonctions isValidInsee et isValidPhone retournent vrai
     * pour chacun des paramètres respectifs
     * 
     * @param string $filePath
     * @param \Symfony\Component\Console\Output\OutputInterface $outputInterface
     * @throws \RuntimeException
     * @return array[]
     */
    private function parseCsvRows(string $filePath): array {
        // open file
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new \RuntimeException("Cannot open file");
        }

        $batchData = [];
        while (($row = fgetcsv($handle, 0, ',', '"', "\\")) !== false) {
            $this->rowNumber++;

            if (count($row) < 2) {
                $this->loggerInterface->info('Skipping invalid row #' . $this->rowNumber);
                $this->errorCount++;
                continue;
            }
            
            [$insee, $telephone] = $row;
            
            if (!$this->isValidInsee($insee) || !$this->isValidPhone($telephone)) {
                $this->loggerInterface->info('Invalid data at row #' . $this->rowNumber);
                $this->errorCount++;
                continue;
            }
            
            $batchData[] = [self::INSEE_INDEX => $insee, self::TELEPHONE_INDEX => $telephone];
            $this->successCount++;
            $this->loggerInterface->info("Row #" . $this->rowNumber . ": INSEE=" . $insee . ", PHONE:" . $telephone);
        }

        fclose($handle);

        return $batchData;
    }

    /**
     * batchInsert permet de sauvegarder efficacement des données en base de données.
     * L'idée est d'envoyer les données sous forme de chunks afin de minimiser les requêtes SQL.
     * 
     * @param array $data
     * @param int $batchSize
     * @throws \RuntimeException
     * @return void
     */
    private function batchInsert(array $data, int $batchSize = 100): void {
        if (empty($data)) {
            throw new \RuntimeException("data is empty, nothing to insert");
        }

        $chunks = array_chunk($data, $batchSize);

        foreach ($chunks as $chunk) {
            $placeholders = [];
            $values = [];

            foreach ($chunk as $row) {
                $placeholders[] = "(?, ?)";
                $values[] = $row[self::INSEE_INDEX];
                $values[] = $row[self::TELEPHONE_INDEX];
            }

            $sql = "INSERT INTO destinataires (insee, telephone) VALUES " 
            . implode(", ", $placeholders) . 
            " ON CONFLICT (telephone) DO NOTHING";
            
            $this->connection->executeStatement($sql, $values);
        }
    }

    /**
     * printReport permet d'afficher un rapport de l'exécution de la commande app:csv-import
     * Elle prend en paramètre le nombre de succès et le nombre d'erreurs
     * 
     * @param \Symfony\Component\Console\Output\OutputInterface $outputInterface
     * @return void
     */
    private function printReport(OutputInterface $outputInterface): void {
        $outputInterface->writeln("\n<info>CSV IMPORT REPORT</info>");
        $outputInterface->writeln("<comment>===================</comment>\n");

        $outputInterface->writeln("<info>Total rows:</info>  " . $this->rowNumber);
        $outputInterface->writeln("<info>✔ Success Count:</info>  " . $this->successCount);
        $outputInterface->writeln("<error>✖ Error Count:</error>  " . $this->errorCount);

        if ($this->errorCount > 0) {
            $outputInterface->writeln("\n<comment>⚠ Some rows were skipped due to errors.</comment>");
        }

        $outputInterface->writeln("\n<comment>===================</comment>\n");
    }
}
