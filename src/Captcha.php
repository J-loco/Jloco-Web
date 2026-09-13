<?php

declare(strict_types=1);

namespace StarLoco\Web;

use StarLoco\Web\Security\Session;

/**
 * Image captcha for registration. The expected code lives in the session and is single use.
 */
final readonly class Captcha
{
    private const string SESSION_KEY = '_captcha';
    // No 0/O, 1/I/L: easy to confuse in a small image.
    private const string ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    private const int LENGTH = 6;

    public function __construct(private Session $session)
    {
    }

    /** Generates a new code, stores it in the session and returns the PNG bytes. */
    public function generate(): string
    {
        $code = '';
        for ($i = 0; $i < self::LENGTH; $i++) {
            $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }
        $this->session->set(self::SESSION_KEY, $code);

        $width = 130;
        $height = 36;
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 245, 240, 230));

        for ($i = 0; $i < 6; $i++) {
            $line = imagecolorallocate($image, random_int(150, 210), random_int(150, 210), random_int(150, 210));
            imageline($image, random_int(0, $width), 0, random_int(0, $width), $height, $line);
        }

        $ink = imagecolorallocate($image, 40, 30, 20);
        foreach (str_split($code) as $i => $char) {
            imagechar($image, 5, 12 + $i * 19, random_int(4, 16), $char, $ink);
        }

        ob_start();
        imagepng($image);
        imagedestroy($image);
        return (string) ob_get_clean();
    }

    /** Checks the answer (case-insensitive) and consumes the code whatever the result. */
    public function verify(string $answer): bool
    {
        $expected = $this->session->pull(self::SESSION_KEY);
        return is_string($expected) && hash_equals($expected, strtoupper(trim($answer)));
    }
}
