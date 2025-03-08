<?php

use PHPUnit\Framework\TestCase;
use App\Service\DataValidatorService;

class DataValidatorServiceTest extends TestCase
{
    private DataValidatorService $validator;

    protected function setUp(): void
    {
        $this->validator = new DataValidatorService();
    }

    public function testValideInseeCode(): void
    {
        $this->assertTrue($this->validator->isValidInseeCode('75056'), '75056 devrait être un code INSEE valide...');
        $this->assertTrue($this->validator->isValidInseeCode('2A123'), '2A123 devrait être un code INSEE valide...');
        $this->assertTrue($this->validator->isValidInseeCode('2B456'), '2B456 devrait être un code INSEE valide...');
    }

    public function testInvalidInseeCode(): void
    {
        $this->assertFalse($this->validator->isValidInseeCode('71'), '71 est trop court pour un code INSEE');
        $this->assertFalse($this->validator->isValidInseeCode('A75056'), 'A75056 ne correspond pas au format INSEE');
        $this->assertFalse($this->validator->isValidInseeCode('2C123'), '2C123 n\'est pas autorisé');
        $this->assertFalse($this->validator->isValidInseeCode('7505a'), '7505a contient un caractère invalide');
    }

    public function testValidPhoneNumber(): void
    {
        $this->assertTrue($this->validator->isValidPhoneNumber('+33123456789'), '+33123456789 devrait être un numéro valide');
        $this->assertTrue($this->validator->isValidPhoneNumber('0123456789'), '0123456789 devrait être un numéro valide');
        $this->assertTrue($this->validator->isValidPhoneNumber('+123456789012345'), 'Numéro avec 15 chiffres est valide');
    }

    public function testInvalidPhoneNumber(): void
    {
        $this->assertFalse($this->validator->isValidPhoneNumber('12345'), '12345 est trop court');
        $this->assertFalse($this->validator->isValidPhoneNumber('+1234567890123456'), 'Numéro avec 16 chiffres est trop long');
        $this->assertFalse($this->validator->isValidPhoneNumber('01234abcde'), '01234abcde contient des lettres et n\'est pas valide');
    }
}