<?php

	namespace Frontpack\ComposerAssetsPlugin;

	use Composer;
	use Composer\Installer\PackageEvent;
	use Composer\Installer\PackageEvents;


	class ComposerAssetsPlugin implements Composer\Plugin\PluginInterface, Composer\EventDispatcher\EventSubscriberInterface, Composer\Plugin\Capable
	{
		/** @var Composer\IO\IOInterface */
		private $io;

		/** @var Composer\Util\Filesystem */
		private $filesystem;


		/**
		 * @return void
		 */
		public function activate(Composer\Composer $composer, Composer\IO\IOInterface $io)
		{
			$this->io = $io;
			$this->filesystem = new Composer\Util\Filesystem;
		}


		/**
		 * @return void
		 */
		public function deactivate(Composer\Composer $composer, Composer\IO\IOInterface $io)
		{
		}


		/**
		 * @return void
		 */
		public function uninstall(Composer\Composer $composer, Composer\IO\IOInterface $io)
		{
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
				Composer\Script\ScriptEvents::POST_UPDATE_CMD => 'processScriptEvent',
				Composer\Script\ScriptEvents::POST_INSTALL_CMD => 'processScriptEvent',
			);
		}


		/**
		 * @return void
		 */
		public function processScriptEvent(Composer\Script\Event $event)
		{
			$installer = new AssetsInstaller($event->getComposer(), $this->io, $this->filesystem);
			$installer->process();
		}
	}
