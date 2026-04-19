<?php

declare(strict_types=1);

/**
 * Copyright 2014-2026 TrueServer B.V.
 * Copyright 2015-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Renan Gonçalves <renan.saddam@gmail.com>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Idna
 * @link     http://tools.ietf.org/html/rfc3492
 */

namespace Horde\Idna\Backend;

use Horde\Util\HordeString;

class Punycode implements BackendInterface
{
    private const int BASE         = 36;
    private const int TMIN         = 1;
    private const int TMAX         = 26;
    private const int SKEW         = 38;
    private const int DAMP         = 700;
    private const int INITIAL_BIAS = 72;
    private const int INITIAL_N    = 128;
    private const string PREFIX    = 'xn--';
    private const string DELIMITER = '-';

    /** @var array<string> */
    private const array ENCODE_TABLE = [
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l',
        'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x',
        'y', 'z', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
    ];

    /** @var array<string, int> */
    private const array DECODE_TABLE = [
        'a' =>  0, 'b' =>  1, 'c' =>  2, 'd' =>  3, 'e' =>  4, 'f' =>  5,
        'g' =>  6, 'h' =>  7, 'i' =>  8, 'j' =>  9, 'k' => 10, 'l' => 11,
        'm' => 12, 'n' => 13, 'o' => 14, 'p' => 15, 'q' => 16, 'r' => 17,
        's' => 18, 't' => 19, 'u' => 20, 'v' => 21, 'w' => 22, 'x' => 23,
        'y' => 24, 'z' => 25, '0' => 26, '1' => 27, '2' => 28, '3' => 29,
        '4' => 30, '5' => 31, '6' => 32, '7' => 33, '8' => 34, '9' => 35,
    ];

    public function encode(string $domain): string
    {
        $parts = explode('.', $domain);

        foreach ($parts as &$part) {
            $part = $this->encodePart($part);
        }

        return implode('.', $parts);
    }

    public function decode(string $domain): string
    {
        $parts = explode('.', $domain);

        foreach ($parts as &$part) {
            if (str_starts_with(HordeString::lower($part), self::PREFIX)) {
                $part = $this->decodePart(
                    substr($part, strlen(self::PREFIX)),
                );
            }
        }

        return implode('.', $parts);
    }

    private function encodePart(string $input): string
    {
        $codePoints = $this->listCodePoints($input);

        $n = self::INITIAL_N;
        $bias = self::INITIAL_BIAS;
        $delta = 0;
        $h = $b = count($codePoints['basic']);

        $output = '';
        foreach ($codePoints['basic'] as $code) {
            $output .= $this->codePointToChar($code);
        }
        if ($input === $output) {
            return $output;
        }
        if ($b > 0) {
            $output .= self::DELIMITER;
        }

        $codePoints['nonBasic'] = array_unique($codePoints['nonBasic']);
        sort($codePoints['nonBasic']);

        $i = 0;
        $length = HordeString::length($input, 'UTF-8');

        while ($h < $length) {
            $m = $codePoints['nonBasic'][$i++];
            $delta = $delta + ($m - $n) * ($h + 1);
            $n = $m;

            foreach ($codePoints['all'] as $c) {
                if ($c < $n || $c < self::INITIAL_N) {
                    ++$delta;
                }

                if ($c === $n) {
                    $q = $delta;
                    for ($k = self::BASE; ; $k += self::BASE) {
                        $t = $this->calculateThreshold($k, $bias);
                        if ($q < $t) {
                            break;
                        }

                        $code = $t + (($q - $t) % (self::BASE - $t));
                        $output .= self::ENCODE_TABLE[$code];

                        $q = ($q - $t) / (self::BASE - $t);
                    }

                    $output .= self::ENCODE_TABLE[$q];
                    $bias = $this->adapt($delta, $h + 1, $h === $b);
                    $delta = 0;
                    ++$h;
                }
            }

            ++$delta;
            ++$n;
        }

        return self::PREFIX . $output;
    }

    private function decodePart(string $input): string
    {
        $n = self::INITIAL_N;
        $i = 0;
        $bias = self::INITIAL_BIAS;
        $output = '';

        $input = HordeString::lower($input);

        $pos = strrpos($input, self::DELIMITER);
        if ($pos !== false) {
            $output = substr($input, 0, $pos++);
        } else {
            $pos = 0;
        }

        $outputLength = strlen($output);
        $inputLength = strlen($input);

        while ($pos < $inputLength) {
            $oldi = $i;
            $w = 1;

            for ($k = self::BASE; ; $k += self::BASE) {
                $digit = self::DECODE_TABLE[$input[$pos++]];
                $i = $i + ($digit * $w);
                $t = $this->calculateThreshold($k, $bias);

                if ($digit < $t) {
                    break;
                }

                $w = $w * (self::BASE - $t);
            }

            $bias = $this->adapt($i - $oldi, ++$outputLength, $oldi === 0);
            $n = $n + (int) ($i / $outputLength);
            $i = $i % $outputLength;

            $output = HordeString::substr($output, 0, $i, 'UTF-8')
                . $this->codePointToChar($n)
                . HordeString::substr($output, $i, $outputLength - 1, 'UTF-8');

            ++$i;
        }

        return $output;
    }

    private function calculateThreshold(int $k, int $bias): int
    {
        if ($k <= ($bias + self::TMIN)) {
            return self::TMIN;
        }

        if ($k >= ($bias + self::TMAX)) {
            return self::TMAX;
        }

        return $k - $bias;
    }

    private function adapt(int $delta, int $numPoints, bool $firstTime): int
    {
        $delta = (int) (
            $firstTime
                ? $delta / self::DAMP
                : $delta / 2
        );
        $delta += (int) ($delta / $numPoints);

        $k = 0;
        while ($delta > ((self::BASE - self::TMIN) * self::TMAX) / 2) {
            $delta = (int) ($delta / (self::BASE - self::TMIN));
            $k += self::BASE;
        }

        return $k + (int) (((self::BASE - self::TMIN + 1) * $delta) / ($delta + self::SKEW));
    }

    /**
     * @return array{all: list<int>, basic: list<int>, nonBasic: list<int>}
     */
    private function listCodePoints(string $input): array
    {
        $codePoints = [
            'all'      => [],
            'basic'    => [],
            'nonBasic' => [],
        ];

        $len = HordeString::length($input, 'UTF-8');
        for ($i = 0; $i < $len; ++$i) {
            $char = HordeString::substr($input, $i, 1, 'UTF-8');
            $code = $this->charToCodePoint($char);
            if ($code < 128) {
                $codePoints['all'][] = $codePoints['basic'][] = $code;
            } else {
                $codePoints['all'][] = $codePoints['nonBasic'][] = $code;
            }
        }

        return $codePoints;
    }

    private function charToCodePoint(string $char): int
    {
        $code = ord($char[0]);

        if ($code < 128) {
            return $code;
        }

        if ($code < 224) {
            return (($code - 192) * 64) + (ord($char[1]) - 128);
        }

        if ($code < 240) {
            return (($code - 224) * 4096) + ((ord($char[1]) - 128) * 64) + (ord($char[2]) - 128);
        }

        return (($code - 240) * 262144) + ((ord($char[1]) - 128) * 4096) + ((ord($char[2]) - 128) * 64) + (ord($char[3]) - 128);
    }

    private function codePointToChar(int $code): string
    {
        if ($code <= 0x7F) {
            return chr($code);
        }

        if ($code <= 0x7FF) {
            return chr(($code >> 6) + 192) . chr(($code & 63) + 128);
        }

        if ($code <= 0xFFFF) {
            return chr(($code >> 12) + 224) . chr((($code >> 6) & 63) + 128) . chr(($code & 63) + 128);
        }

        return chr(($code >> 18) + 240) . chr((($code >> 12) & 63) + 128) . chr((($code >> 6) & 63) + 128) . chr(($code & 63) + 128);
    }
}
