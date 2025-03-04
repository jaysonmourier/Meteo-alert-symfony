<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Console\Output\OutputInterface;

class ImportReportService
{
    /**
     * La méthode generate permet de générer un rapport pour informer l'utilisateur
     * sur la finalité de l'exécution de la commande app:csv-import.
     *
     * Elle affiche:
     *  - le nombre total de lignes lues
     *  - Le nombre total de lignes correctement parsées
     *  - Le nombre total de lignes insérées en base
     *  - Le nombre total d'erreurs
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param int $totalRows
     * @param int $totalSuccessRows
     * @param int $totalInsertedRows
     * @param int $totalErrorRows
     * @return void
     */
    public function generate(
        OutputInterface $output,
        int $totalRows,
        int $totalSuccessRows,
        int $totalInsertedRows,
        int $totalErrorRows
    ): void {
        $output->writeln("\n<info>CSV IMPORT REPORT</info>");
        $output->writeln("<comment>===================</comment>\n");

        $output->writeln("<info>Total rows:</info>  " . $totalRows);
        $output->writeln("<info>✔ Success Count:</info>  " . $totalSuccessRows);
        $output->writeln("<info>✔ Inserted Rows:</info>  " . $totalInsertedRows);
        $output->writeln("<error>✖ Error Count:</error>  " . $totalErrorRows);

        if ($totalErrorRows > 0) {
            $output->writeln("\n<comment>⚠ Some rows were skipped due to errors.</comment>");
        }

        $output->writeln("\n<comment>===================</comment>\n");
    }
}
