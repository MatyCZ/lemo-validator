<?php

namespace Lemo\Validator;

use Laminas\Validator\AbstractValidator;

use function array_key_exists;
use function is_string;
use function preg_match;
use function str_pad;

use const STR_PAD_LEFT;

class BankAccountCZ extends AbstractValidator
{
    public const INVALID_ACCOUNT_NUMBER = 'invAccount';
    public const INVALID_ACCOUNT_PREFIX = 'invPrefix';
    public const INVALID_BANK_CODE = 'invBankCode';
    public const INVALID_INPUT = 'invInput';

    /**
     * Číselník bankovních kódů k datu 1.2.2014 (zdroj ČNB)
     *
     * @var array<string|string>
     */
    protected array $bankCodes = [
        '0100' => 'Komerční banka, a.s.',
        '0300' => 'Československá obchodní banka, a. s.',
        '0600' => 'MONETA Money Bank, a.s.',
        '0710' => 'Česká národní banka',
        '0800' => 'Česká spořitelna, a.s.',
        '2010' => 'Fio banka, a.s.',
        '2020' => 'MUFG Bank (Europe) N.V. Prague Branch',
        '2070' => 'TRINITY BANK a.s.',
        '2100' => 'Hypoteční banka, a.s.',
        '2240' => 'Poštová banka, a.s., pobočka Česká republika',
        '2250' => 'Banka CREDITAS a.s.',
        '2600' => 'Citibank Europe plc, organizační složka',
        '2700' => 'UniCredit Bank Czech Republic and Slovakia, a.s.',
        '3030' => 'Air Bank a.s.',
        '3040' => 'Western Union International Bank GmbH, organizační složka',
        '3050' => 'Hello bank! (BNP Paribas Personal Finance SA, odštěpný závod)',
        '3060' => 'PKO BP S.A., Czech Branch',
        '3500' => 'ING Bank N.V.',
        '4000' => 'Expobank CZ a.s.',
        '4300' => 'Českomoravská záruční a rozvojová banka, a.s.',
        '5500' => 'Raiffeisenbank a.s.',
        '5800' => 'J&T BANKA, a.s.',
        '6000' => 'PPF banka a.s.',
        '6100' => 'Equa bank a.s.',
        '6200' => 'COMMERZBANK Aktiengesellschaft, pobočka Praha',
        '6210' => 'mBank S.A., organizační složka',
        '6300' => 'BNP Paribas S.A., pobočka Česká republika',
        '6700' => 'Všeobecná úverová banka a.s., pobočka Praha; zkráceně: VUB, a.s., pobočka Praha',
        '6800' => 'Sberbank CZ, a.s.',
        '7101' => 'Privatbanka, a.s., pobočka Česká republika',
        '7201' => 'PARTNER BANK AKTIENGESELLSCHAFT, odštěpný závod',
        '7910' => 'Deutsche Bank Aktiengesellschaft Filiale Prag, organizační složka',
        '7940' => 'Waldviertler Sparkasse Bank AG',
        '7950' => 'Raiffeisen stavební spořitelna a.s.',
        '7960' => 'Českomoravská stavební spořitelna, a.s.',
        '7970' => 'MONETA Stavební Spořitelna, a.s.',
        '7990' => 'Modrá pyramida stavební spořitelna, a.s.',
        '8030' => 'Volksbank Raiffeisenbank Nordoberpfalz eG pobočka Cheb',
        '8040' => 'Oberbank AG pobočka Česká republika',
        '8060' => 'Stavební spořitelna České spořitelny, a.s.',
        '8090' => 'Česká exportní banka, a.s.',
        '8150' => 'HSBC Continental Europe, Czech Republic',
        '8200' => 'PRIVAT BANK der Raiffeisenlandesbank Oberösterreich Aktiengesellschaft, pobočka Česká republika',
        '8211' => 'Saxo Bank A/S, organizační složka',
        '8231' => 'Bank Gutmann Aktiengesellschaft, pobočka Česká republika',
        '8241' => 'SMBC Bank EU AG Prague Branch',
        '8250' => 'Bank of China (CEE) Ltd. Prague Branch',
        '8255' => 'Bank of Communications Co., Ltd.,  Prague Branch odštěpný závod',
        '8265' => 'Industrial and Commercial Bank of China Limited, Prague Branch, odštěpný závod',
        '9999' => 'Fiktivní banka',
    ];

    /**
     * @var array<string|string>
     */
    protected array $messageTemplates = [
        self::INVALID_INPUT => 'Invalid input',
        self::INVALID_ACCOUNT_PREFIX => 'Invalid bank account prefix',
        self::INVALID_ACCOUNT_NUMBER => 'Invalid bank account main part',
        self::INVALID_BANK_CODE => 'Invalid bank code',
    ];

    /**
     * @param  mixed $value
     * @return bool
     */
    public function isValid($value): bool
    {
        if (!is_string($value)) {
            $this->error(self::INVALID_INPUT);
            return false;
        }

        $this->setValue($value);
        if (!preg_match('#^\s*((\d{0,6})-)?(\d{1,10})\/(\d{4})\s*$#', $value, $matches)) {
            $this->error(self::INVALID_INPUT);
            return false;
        }

        [, , $accountPrefix, $accountNo, $bankCode] = $matches;

        // validace předčíslí
        if (!empty($accountPrefix)) {
            $accountPrefix = str_pad($accountPrefix, 6, '0', STR_PAD_LEFT);

            $sum = 0;
            foreach ([10, 5, 8, 4, 2, 1] as $index => $weight) {
                $sum += (int) $accountPrefix[$index] * $weight;
            }

            if (0 !== $sum % 11) {
                $this->error(self::INVALID_ACCOUNT_PREFIX);
                return false;
            }
        }

        // validace čísla účtu
        $accountNo = str_pad($accountNo, 10, '0', STR_PAD_LEFT);

        $sum = 0;
        foreach ([6, 3, 7, 9, 10, 5, 8, 4, 2, 1] as $index => $weight) {
            $sum += (int) $accountNo[$index] * $weight;
        }

        if (0 !== $sum % 11) {
            $this->error(self::INVALID_ACCOUNT_NUMBER);
            return false;
        }

        // validace kódu banky
        if (!array_key_exists($bankCode, $this->bankCodes)) {
            $this->error(self::INVALID_BANK_CODE);
            return false;
        }

        return true;
    }
}
