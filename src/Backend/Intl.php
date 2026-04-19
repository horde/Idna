<?php

declare(strict_types=1);

/**
 * Copyright 2014-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Idna
 */

namespace Horde\Idna\Backend;

use Horde\Idna\Exception;

class Intl implements BackendInterface
{
    public function encode(string $domain): string
    {
        $result = idn_to_ascii($domain, 0, INTL_IDNA_VARIANT_UTS46, $info);
        $this->checkForError($info);

        return $result !== false ? $result : '';
    }

    public function decode(string $domain): string
    {
        $parts = explode('.', $domain);

        foreach ($parts as &$part) {
            if (str_starts_with($part, 'xn--')) {
                $decoded = idn_to_utf8($part, 0, INTL_IDNA_VARIANT_UTS46, $info);
                $this->checkForError($info);
                $part = $decoded !== false ? $decoded : $part;
            }
        }

        return implode('.', $parts);
    }

    /**
     * @param array<string, mixed> $info
     *
     * @throws Exception
     */
    private function checkForError(array $info): void
    {
        if (!isset($info['errors']) || $info['errors'] === 0) {
            return;
        }

        $errors = $info['errors'];

        $message = match (true) {
            (bool) ($errors & IDNA_ERROR_EMPTY_LABEL) => 'Domain name is empty',
            (bool) ($errors & IDNA_ERROR_LABEL_TOO_LONG),
            (bool) ($errors & IDNA_ERROR_DOMAIN_NAME_TOO_LONG) => 'Domain name is too long',
            (bool) ($errors & IDNA_ERROR_LEADING_HYPHEN) => 'Starts with a hyphen',
            (bool) ($errors & IDNA_ERROR_TRAILING_HYPHEN) => 'Ends with a hyphen',
            (bool) ($errors & IDNA_ERROR_HYPHEN_3_4) => 'Contains hyphen in the third and fourth positions',
            (bool) ($errors & IDNA_ERROR_LEADING_COMBINING_MARK) => 'Starts with a combining mark',
            (bool) ($errors & IDNA_ERROR_DISALLOWED) => 'Contains disallowed characters',
            (bool) ($errors & IDNA_ERROR_PUNYCODE) => 'Starts with "xn--" but does not contain valid Punycode',
            (bool) ($errors & IDNA_ERROR_LABEL_HAS_DOT) => 'Contains a dot',
            (bool) ($errors & IDNA_ERROR_INVALID_ACE_LABEL) => 'ACE label does not contain a valid label string',
            (bool) ($errors & IDNA_ERROR_BIDI) => 'Does not meet the IDNA BiDi requirements (for right-to-left characters)',
            (bool) ($errors & IDNA_ERROR_CONTEXTJ) => 'Does not meet the IDNA CONTEXTJ requirements',
            default => 'Unknown IDNA error',
        };

        throw new Exception($message);
    }
}
