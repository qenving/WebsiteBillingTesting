<?php

namespace App\Support;

class ModuleToggle
{
    public static function gateways(): array
    {
        $val = Settings::get('payments.enabled', []);
        return is_array($val) ? $val : (array) $val;
    }

    public static function provisioning(): array
    {
        $val = Settings::get('provisioning.enabled', []);
        return is_array($val) ? $val : (array) $val;
    }
}

