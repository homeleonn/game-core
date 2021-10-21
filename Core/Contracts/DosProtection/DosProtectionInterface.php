<?php

namespace Core\Contracts\DosProtection;

interface DosProtectionInterface
{
    public function isValid(string $ip): bool;
}
