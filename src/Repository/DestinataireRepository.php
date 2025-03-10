<?php

declare(strict_types=1);

namespace App\Repository;

use Exception;
use RuntimeException;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class DestinataireRepository
{
    private const TABLE_NAME = 'destinataires';
    private const INSEE_KEY = 'insee';
    private const TELEPHONE_KEY = 'telephone';

    public function __construct(private Connection $connection, private LoggerInterface $logger)
    {
    }

    /**
     * Cette méthode retourne les numéros de téléphone associé au code INSEE donné en paramètre.
     *
     * En cas d'erreur, elle retourne une exception.
     *
     * @param string $insee
     * @throws \RuntimeException
     * @return array
     */
    public function getNumbersByInsee(string $insee): array
    {
        $sql = "SELECT DISTINCT telephone FROM " . self::TABLE_NAME . " WHERE insee = :insee";

        try {
            $res = $this->connection
                ->executeQuery($sql, ['insee' => $insee])
                ->fetchAllAssociative();
        } catch (Exception $e) {
            throw new RuntimeException(
                "Erreur lors de l'exécution de la requête SQL (getNumbersFromInsee)",
                0,
                $e
            );
        }

        return array_column($res, 'telephone');
    }

    /**
     * La méthode insertBulk permet de persister efficacement les données en base de données.
     * Elle repose sur un système de chunks. Par défaut, la taille d'un chunk est de 10 éléments.
     * L'objectif est de minimiser le nombre de requêtes faites vers la base de données.
     *
     * insertBulk retourne le nombre de lignes insérées en base de données.
     *
     * La méthode peut lever une exception si 'executeStatement' échoue
     *
     * La méthode fonctionne comme tel:
     * - Elle découpe le tableau en plusieurs chunks de taille $chunkSize
     * - Pour chacun des éléments des chunks :
     *      . on génère son placeholder (?, ?) pour l'injecter dans la requête SQL
     *      . on ajoute son code INSEE au tableau 'values'
     *      . on ajoute son numéro de téléphone au tableau 'values'
     *      . on injecte le placeholder est le tableau 'values' dans une requête SQL
     *      . on exécute la requête via la méthode executeStatement
     *
     *
     * @param array $data
     * @param int $chunkSize
     * @throws \RuntimeException
     * @return int
     */
    public function insertBulk(array $data, int $chunkSize = 25): int
    {
        if (empty($data)) {
            return 0;
        }

        $this->logger->info("BEGIN TRANSACTION");
        $this->connection->beginTransaction(); 
        $insertedRows = 0;

        try {
            foreach (array_chunk($data, $chunkSize) as $chunk) {
                $placeholders = [];
                $values = [];

                foreach ($chunk as $row) {
                    $placeholders[] = "(?, ?)";
                    $values[] = $row[self::INSEE_KEY];
                    $values[] = $row[self::TELEPHONE_KEY];
                }

                $sql = "INSERT INTO " . self::TABLE_NAME . " (insee, telephone) VALUES "
                . implode(", ", $placeholders) . " ON CONFLICT (insee, telephone) DO NOTHING;";

                $insertedRows += $this->connection->executeStatement($sql, $values);
            }

            $this->logger->info("TRANSACTION: COMMIT");
            $this->connection->commit();
        } catch (Exception $e) {
            $this->logger->error("TRANSACTION: ROLLBACK");
            $this->connection->rollBack();
            throw new RuntimeException(
                "Erreur lors de l'insertion des données dans la table `" . self::TABLE_NAME . "`",
                0,
                $e
            );
        }

        $this->logger->info("TRANSACTION: DONE");

        return $insertedRows;
    }

}
