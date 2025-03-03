<?php

declare(strict_types=1);

namespace App\Command;

use Exception;
use Psr\Log\LoggerInterface;
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
    public const INSEE_INDEX = "insee";
    public const TELEPHONE_INDEX = "telephone";

    private int $rowNumber;
    private int $successCount;
    private int $errorsCount;
    private int $insertedRows;

    public function __construct(
        private LoggerInterface $logger,
        private DestinataireRepository $destinataireRepository
    ) {
        parent::__construct();
        $this->rowNumber = 0;
        $this->successCount = 0;
        $this->errorsCount = 0;
        $this->insertedRows = 0;
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

            // check if file exists
            if (!file_exists($filePath)) {
                $outputInterface->writeln('<error>File not found: ' . $filePath . '</error>');
                return Command::FAILURE;
            }

            // parse CSV and persist data
            $validRows = $this->parseCsvRows($filePath);
            if (!empty($validRows)) {
                $this->insertedRows = $this->destinataireRepository->insertBulk($validRows);
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
     * isValidInsee permet de vérifier que le code INSEE passé en paramètre est bien un code INSEE valide.
     * Si le code INSEE est valide, la méthode retourne 'true'. Sinon, elle retourne 'false'.
     * 
     * @param string $insee
     * @return bool
     */
    private function isValidInsee(string $insee): bool
    {
        return $insee !== null && (bool)preg_match('/^\d{5}$/', $insee);
    }

    /**
     * isValidPhone permet de vérifier que le numéro de téléphone passé en paramètre est bien un numéro valide.
     * Si le numéro est valide, la méthode retourne 'true'. Sinon, elle retourne 'false'.
     * 
     * @param string $phone
     * @return bool
     */
    private function isValidPhone(string $phone): bool
    {
        return $phone !== null && (bool)preg_match('/^\+?\d{10,15}$/', $phone);
    }

    /**
     * Cette méthode permet d'ouvrir le fichier CSV situé au chemin $filePath donné en paramètre,
     * d'ouvrir et parcourir ligne par ligne le fichier CSV afin d'en extraire les lignes valides.
     * 
     * Une ligne est considérée comme valide si le couple (INSEE, TELEPHONE) extrait, satisfait bien les 
     * méthodes isValidInsee et isValidPhone.
     * 
     * Si aucun couple (INSEE, TELEPHONE) n'est valide, alors la fonction retourne un tableau vide
     * 
     * Cette méthode fonctionne comme tel: 
     * - Elle ouvre le fichier relatif au chemin $filePath. En cas d'erreur, une exception est levée.
     * - Elle boucle avec comme condition de sortie, le retour de la fonction fgetcsv.
     * - Pour chaque ligne, parseCsvRows vérifie que le code INSEE et le numéro de téléphone extrait soit bien valide.
     * - Si le couple (INSEE, TELEPHONE) est valide, elle l'ajoute au tableau des couples valides.
     * - Une fois terminé, la fonction retourne le tableau avec tous les couples valides du fichier CSV.
     * 
     * @param string $filePath
     * @throws \RuntimeException
     * @return array
     */
    private function parseCsvRows(string $filePath): array {
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new \RuntimeException("Cannot open file");
        }

        $batchData = [];
        while (($row = fgetcsv($handle, 0, ',', '"', "\\")) !== false) {
            $this->rowNumber++;

            if (count($row) < 2) {
                $this->errorsCount++;
                $this->logger->error('Skipping invalid row #' . $this->rowNumber);
                continue;
            }
            
            [$insee, $telephone] = $row;
            
            if (!$this->isValidInsee($insee) || !$this->isValidPhone($telephone)) {
                $this->errorsCount++;
                $this->logger->error('Invalid data at row #' . $this->rowNumber);
                continue;
            }
            
            $batchData[] = [self::INSEE_INDEX => $insee, self::TELEPHONE_INDEX => $telephone];
            $this->successCount++;
            $this->logger->info("Row #" . $this->rowNumber . ": INSEE=" . $insee . ", PHONE:" . $telephone);
        }

        fclose($handle);

        return $batchData;
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
