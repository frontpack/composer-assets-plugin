<?php

namespace Frontpack\ComposerAssetsPlugin;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Composer\Util\Filesystem;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;

class ComposerAssetsPlugin implements PluginInterface,EventSubscriberInterface, Capable
{
    /** @var IOInterface */
    private $io;

    /** @var Filesystem */
    private $filesystem;


    /**
     * @return void
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->io = $io;
        $this->filesystem = new Filesystem;
    }


    public function getCapabilities()
    {
        return array(
            'Composer\Plugin\Capability\CommandProvider' => 'Frontpack\ComposerAssetsPlugin\CommandProvider',
        );
    }


    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            ScriptEvents::POST_UPDATE_CMD => 'processScriptEvent',
            ScriptEvents::POST_INSTALL_CMD => 'processScriptEvent',
        );
    }


    /**
     * @return void
     */
    public function processScriptEvent(Event $event)
    {
        $installer = new AssetsInstaller($event->getComposer(), $this->io);
        $installer->process();
    }
}
