<?php

namespace Fabrikage\QR;

class Bootstrap
{
    public static function init(): void
    {
        PostType\QrCode::init();
    }

    public static function activate(): void
    {
        Database\Tables::create();
    }

    public static function deactivate(): void
    {
        Database\Tables::drop();
    }
}
