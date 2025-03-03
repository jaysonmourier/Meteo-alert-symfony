<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\ImportCsvCommand;
use App\Repository\DestinataireRepository;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ImportCsvCommandTest extends TestCase
{
    private $connectionMock;
    private $loggerMock;
    private $destinataireRepository;

    protected function setUp(): void {
        $this->connectionMock = $this->createMock(Connection::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->destinataireRepository = new DestinataireRepository($this->connectionMock);
    }

    public function testFileNotFound(): void {
        $command = new ImportCsvCommand($this->loggerMock, $this->destinataireRepository);
        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($command);
        $statusCode = $commandTester->execute([
            'file' => 'random_file_name.csv',
        ]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString('File not found', $output);
        $this->assertSame(Command::FAILURE, $statusCode);
    }

    /**
     * Ce test permet de vérifier que les données invalides sont bien
     * prisent en compte par le programme
     * 
     * @return void
     */
    public function testInvalidRowInCsv(): void {
        // On créé un fichier CSV temporaire
        $csvContent = "12345,+33123test\nabc,+33123456789\nhello world\n\n";
        $tempFile = $this->createTempCsvFile($csvContent);

        // Aucune ligne n'est valide
        // donc executeStatement ne doit jamais
        // être appelé
        $this->connectionMock
            ->expects($this->never())
            ->method('executeStatement');
    
        // Comme aucune ligne n'est valide et qu'on a 4 lignes,
        // on s'attend à avoir 4 erreurs (donc 4 messages d'erreurs)
        $this->loggerMock
            ->expects($this->exactly(4))
            ->method("error");

        $command = new ImportCsvCommand($this->loggerMock, $this->destinataireRepository);
        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($command);
        $statusCode = $commandTester->execute([
            'file' => $tempFile,
        ]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString('CSV IMPORT REPORT', $output);
        $this->assertStringContainsString("Total rows:  4", $output);
        $this->assertStringContainsString("Success Count:  0", $output);
        $this->assertStringContainsString("Error Count:  4", $output);
        $this->assertSame(Command::SUCCESS, $statusCode);

        unlink($tempFile);
    }

    public function testvalidRowInCsv(): void {
        $csvContent = "12345,+33123456789\n";
        $tempFile = $this->createTempCsvFile($csvContent);
        
        $this->connectionMock
            ->expects($this->once())
            ->method('executeStatement');

        $this->loggerMock
            ->expects($this->once())
            ->method('info');

        $this->loggerMock
            ->expects($this->never())
            ->method('error');

        $command = new ImportCsvCommand($this->loggerMock, $this->destinataireRepository);
        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($command);
        $statusCode = $commandTester->execute([
            'file' => $tempFile,
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('CSV IMPORT REPORT', $output);
        $this->assertStringContainsString('Total rows:  1', $output);
        $this->assertStringContainsString('Success Count:  1', $output);
        $this->assertStringContainsString('Error Count:  0', $output);
        $this->assertSame(Command::SUCCESS, $statusCode);

        unlink($tempFile);
    }

    /**
     * Cette fonction utilitaire permet de créer un fichier CSV temporaire
     * et de lui attribuer un contenu
     * 
     * @param string $csvContent
     * @return bool|string
     */
    private function createTempCsvFile(string $csvContent): bool|string {
        $tempFile = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($tempFile, $csvContent);
        return $tempFile;
    }
}
