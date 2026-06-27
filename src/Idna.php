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

namespace Horde\Idna;

use Horde\Idna\Backend\BackendInterface;
use Horde\Idna\Backend\Intl;
use Horde\Idna\Backend\Punycode;

class Idna
{
    public function __construct(
        private readonly BackendInterface $backend,
    ) {}

    public static function create(): self
    {
        $backend = extension_loaded('intl')
            ? new Intl()
            : new Punycode();

        return new self($backend);
    }

    /**
     * @throws Exception
     */
    public function encode(string $domain): string
    {
        if ($domain === '') {
            return '';
        }

        return $this->backend->encode($domain);
    }

    /**
     * @throws Exception
     */
    public function decode(string $domain): string
    {
        if ($domain === '') {
            return '';
        }

        return $this->backend->decode($domain);
    }
}
