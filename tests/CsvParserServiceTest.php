<?php

use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use App\Service\CsvParserService;
use App\Service\DataValidatorService;
use App\Exceptions\FileNotFoundException;

class CsvParserServiceTest extends TestCase
{
    private LoggerInterface $logger;
    private DataValidatorService $validator;
    private CsvParserService $parser;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->validator = new DataValidatorService();
        $this->parser = new CsvParserService($this->logger, $this->validator);
    }

    public function testSuccessWithValidRows(): void
    {
        $csvContent = "75001,+33612345678\n75002,0612345678\n75003,+33698765432\n2B456,+123456789012145";

        $filePath = $this->createTmpFile("testSuccessWithValidRows.csv", $csvContent);
        if (!$filePath) {
            $this->assertTrue(false, "impossible de créer le fichier temporaire pour le test");
        }

        $result = $this->parser->parse($filePath);

        $this->assertTrue($result->totalRows == 4, "il devrait y avoir 4 lignes au total");
        $this->assertTrue($result->totalErrorRows == 0, "il ne devrait pas y avoir d'erreur");
        $this->assertTrue($result->totalValidRows == 4, "il devrait y avoir 4 lignes valides");
        unlink($filePath);
    }

    public function testSuccessWithInvalidRows(): void
    {
        $csvContent = "750aa,+33612345678\n75002,0612345678\n75003,+336ABC5432\n2B456,+123456789012145";

        $filePath = $this->createTmpFile("testSuccessWithInvalidRows.csv", $csvContent);
        if (!$filePath) {
            $this->assertTrue(false, "impossible de créer le fichier temporaire pour le test");
        }

        $result = $this->parser->parse($filePath);

        $this->assertTrue($result->totalRows == 4, "il devrait y avoir 4 lignes lues au total");
        $this->assertTrue($result->totalErrorRows == 2, "il devrait y avoir 2 erreurs");
        $this->assertTrue($result->totalValidRows == 2, "il devrait y avoir 2 lignes valides");
        unlink($filePath);
    }

    public function testFileNotFound(): void
    {
        $filePath = '/this/file/doesnt/exists/fake_file.csv';

        $this->expectException(FileNotFoundException::class);

        $this->expectExceptionMessage('Impossible d\'ouvrir le fichier : ' . $filePath);

        $this->parser->parse($filePath);
    }

    public function createTmpFile(string $filePath, string $content): ?string
    {
        $filePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filePath;

        if (false === file_put_contents($filePath, $content)) {
            return null;
        }

        return $filePath;
    }
}