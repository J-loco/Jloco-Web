<?php

declare(strict_types=1);

namespace StarLoco\Web;

/**
 * Image captcha for registration. The expected code lives in the session and is single use.
 */
final class Captcha
{
    private const SESSION_KEY = 'captcha';
    // No 0/O, 1/I/L: easy to confuse in a small image.
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    private const LENGTH = 6;

    /** Generates a new code, stores it in the session and returns the PNG bytes. */
    public static function generate(): string
    {
        $code = '';
        for ($i = 0; $i < self::LENGTH; $i++) {
            $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }
        $_SESSION[self::SESSION_KEY] = $code;

        $width = 130;
        $height = 36;
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 240, 227, 192));

        for ($i = 0; $i < 6; $i++) {
            $line = imagecolorallocate($image, random_int(120, 200), random_int(120, 200), random_int(120, 200));
            imageline($image, random_int(0, $width), 0, random_int(0, $width), $height, $line);
        }

        $ink = imagecolorallocate($image, 30, 30, 30);
        foreach (str_split($code) as $i => $char) {
            imagechar($image, 5, 12 + $i * 19, random_int(4, 16), $char, $ink);
        }

        ob_start();
        imagepng($image);
        imagedestroy($image);
        return (string) ob_get_clean();
    }

    /** Checks the answer (case-insensitive) and consumes the code whatever the result. */
    public static function verify(string $answer): bool
    {
        $expected = $_SESSION[self::SESSION_KEY] ?? null;
        unset($_SESSION[self::SESSION_KEY]);

        return is_string($expected) && hash_equals($expected, strtoupper(trim($answer)));
    }
}
