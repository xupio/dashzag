<?php

namespace App\Support;

use App\Models\User;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\Font\OpenSans;
use Endroid\QrCode\Label\LabelAlignment;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Crypt;

class AdminTwoFactor
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function generateSecret(int $length = 32): string
    {
        $alphabetLength = strlen(self::BASE32_ALPHABET) - 1;
        $secret = '';

        for ($index = 0; $index < $length; $index++) {
            $secret .= self::BASE32_ALPHABET[random_int(0, $alphabetLength)];
        }

        return $secret;
    }

    public static function encryptSecret(string $secret): string
    {
        return Crypt::encryptString($secret);
    }

    public static function decryptSecret(?string $encryptedSecret): ?string
    {
        if (! filled($encryptedSecret)) {
            return null;
        }

        try {
            return Crypt::decryptString($encryptedSecret);
        } catch (\Throwable) {
            return null;
        }
    }

    public static function provisioningUri(User $user, ?string $secret = null): string
    {
        $secret ??= $user->adminTwoFactorSecret();

        if (! $secret) {
            return '';
        }

        $issuer = rawurlencode(config('app.name', 'ZagChain'));
        $label = rawurlencode(config('app.name', 'ZagChain').':'.$user->email);

        return sprintf(
            'otpauth://totp/%s?secret=%s&issuer=%s&algorithm=SHA1&digits=6&period=30',
            $label,
            $secret,
            $issuer,
        );
    }

    public static function provisioningQrDataUri(User $user, ?string $secret = null): ?string
    {
        $uri = self::provisioningUri($user, $secret);

        if ($uri === '') {
            return null;
        }

        $result = (new Builder())->build(
            writer: new PngWriter(),
            writerOptions: [],
            data: $uri,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 240,
            margin: 8,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            labelText: 'Scan with Google Authenticator, 1Password, or similar',
            labelFont: new OpenSans(12),
            labelAlignment: LabelAlignment::Center,
        );

        return $result->getDataUri();
    }

    public static function verifyCodeForUser(User $user, string $code, ?int $timestamp = null, int $window = 1): bool
    {
        $secret = $user->adminTwoFactorSecret();

        return $secret !== null && self::verifyCode($secret, $code, $timestamp, $window);
    }

    public static function verifyCode(string $secret, string $code, ?int $timestamp = null, int $window = 1): bool
    {
        $normalizedCode = preg_replace('/\D+/', '', $code ?? '');

        if ($normalizedCode === null || strlen($normalizedCode) !== 6) {
            return false;
        }

        $timestamp ??= time();
        $currentCounter = (int) floor($timestamp / 30);

        for ($offset = -$window; $offset <= $window; $offset++) {
            if (hash_equals(self::generateCode($secret, $currentCounter + $offset), $normalizedCode)) {
                return true;
            }
        }

        return false;
    }

    public static function currentCode(string $secret, ?int $timestamp = null): string
    {
        $timestamp ??= time();

        return self::generateCode($secret, (int) floor($timestamp / 30));
    }

    public static function currentCodeForUser(User $user, ?int $timestamp = null): ?string
    {
        $secret = $user->adminTwoFactorSecret();

        return $secret ? self::currentCode($secret, $timestamp) : null;
    }

    private static function generateCode(string $secret, int $counter): string
    {
        $secretKey = self::decodeBase32($secret);
        $binaryCounter = pack('N*', 0).pack('N*', $counter);
        $hash = hash_hmac('sha1', $binaryCounter, $secretKey, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $binary = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        );

        return str_pad((string) ($binary % 1_000_000), 6, '0', STR_PAD_LEFT);
    }

    private static function decodeBase32(string $secret): string
    {
        $normalizedSecret = strtoupper(preg_replace('/[^A-Z2-7]/', '', $secret) ?? '');
        $buffer = 0;
        $bitsLeft = 0;
        $decoded = '';

        foreach (str_split($normalizedSecret) as $character) {
            $value = strpos(self::BASE32_ALPHABET, $character);

            if ($value === false) {
                continue;
            }

            $buffer = ($buffer << 5) | $value;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $decoded .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $decoded;
    }
}
