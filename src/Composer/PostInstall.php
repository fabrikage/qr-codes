<?php

namespace Fabrikage\QR\Composer;

use Composer\Script\Event;

class PostInstall
{
    public static function run(Event $event)
    {
        $composer = $event->getComposer();
        $version = $composer->getPackage()->getPrettyVersion();

        $pluginFile = __DIR__ . '/../../fabrikage-qr-codes.php';

        // Replace {version} with the current version
        $content = file_get_contents($pluginFile);
        $content = str_replace('{version}', $version, $content);

        file_put_contents($pluginFile, $content);
    }
}
