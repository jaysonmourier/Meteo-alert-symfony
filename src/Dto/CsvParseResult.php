<?php

declare(strict_types=1);

namespace App\Dto;

class CsvParseResult
{
    public function __construct(
        public int $totalRows,
        public int $totalValidRows,
        public int $totalErrorRows,
        public array $data
    ) {}
}