<?php

declare(strict_types=1);

namespace App\Service;

use RuntimeException;
use App\Dto\CsvParseResult;
use App\Exceptions\FileNotFoundException;
use App\Exceptions\FileOpenException;
use Psr\Log\LoggerInterface;

class CsvParserService
{
    public function __construct(
        private LoggerInterface $logger,
        private DataValidatorService $dataValidatorService
    ) {
    }

    /**
     * Permet d'ouvrir et parcourir un fichier CSV afin d'y extraitre les couples valides (INSEE, telephone).
     * Un couple est valide quand le code INSEE et le numéro de téléphone passe la validation du service App\Service\DataValidatorService
     *
     * @param string $filePath
     * @throws \App\Exceptions\FileNotFoundException
     * @throws \App\Exceptions\FileOpenException
     * @return CsvParseResult
     */
    public function parse(string $filePath): CsvParseResult
    {
        if (!file_exists(filename: $filePath) || !is_readable($filePath)) {
            throw new FileNotFoundException("Impossible d'ouvrir le fichier : $filePath");
        }

        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new FileOpenException("Erreur lors de l'ouverture du fichier : $filePath");
        }

        $totalRows = 0;
        $validRows = 0;
        $errorRows = 0;
        $data = [];

        while (($row = fgetcsv($handle, 0, ',', '"', "\\")) !== false) {
            $totalRows++;

            if (count($row) < 2) {
                $errorRows++;
                $this->logger->error("Ligne #$totalRows ignorée : format invalide.");
                continue;
            }

            [$insee, $telephone] = $row;

            if (
                !$this->dataValidatorService->isValidInseeCode($insee)
                || !$this->dataValidatorService->isValidPhoneNumber($telephone)
            ) {
                $errorRows++;
                $this->logger->error("Ligne #$totalRows invalide : INSEE = $insee, TELEPHONE = $telephone");
                continue;
            }

            $data[] = ['insee' => $insee, 'telephone' => $telephone];
            $validRows++;
        }

        fclose($handle);

        return new CsvParseResult($totalRows, $validRows, $errorRows, $data);
    }
}
