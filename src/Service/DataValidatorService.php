<?php

declare(strict_types=1);

namespace App\Service;

class DataValidatorService
{
    private const INSEE_REGEX_PATTERN = '/^\d{5}$/';
    private const PHONENUMBER_REGEX_PATTERN = '/^\+?\d{10,15}$/';

    /**
     * isValidInseeCode permet de vérifier que le code INSEE passé en paramètre est bien un code INSEE valide.
     * Si le code INSEE est valide, la méthode retourne 'true'. Sinon, elle retourne 'false'.
     *
     * @param string $insee
     * @return bool
     */
    public function isValidInseeCode(string $insee): bool
    {
        return $insee !== null && (bool)preg_match(self::INSEE_REGEX_PATTERN, $insee);
    }

    /**
     * isValidPhoneNumber permet de vérifier que le numéro de téléphone passé en paramètre est bien un numéro valide.
     * Si le numéro est valide, la méthode retourne 'true'. Sinon, elle retourne 'false'.
     *
     * @param string $phone
     * @return bool
     */
    public function isValidPhoneNumber(string $phoneNumber): bool
    {
        return $phoneNumber !== null && (bool)preg_match(self::PHONENUMBER_REGEX_PATTERN, $phoneNumber);
    }
}
