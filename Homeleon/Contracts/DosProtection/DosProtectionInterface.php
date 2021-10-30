<?php

namespace Homeleon\Contracts\DosProtection;

interface DosProtectionInterface
{
    public function isValid(string $ip): bool;
}
