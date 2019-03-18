<?php

namespace Frontpack\ComposerAssetsPlugin;

use Composer\Plugin\Capability\CommandProvider as ComposerCommandProvider;

class CommandProvider implements ComposerCommandProvider
{
    public function getCommands()
    {
        return array(
            new RefreshAssetsCommand(),
        );
    }
}
