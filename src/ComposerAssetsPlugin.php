<?php

	namespace Frontpack\ComposerAssetsPlugin;

	use Composer;
	use Composer\Installer\PackageEvent;
	use Composer\Installer\PackageEvents;


	class ComposerAssetsPlugin implements Composer\Plugin\PluginInterface, Composer\EventDispatcher\EventSubscriberInterface
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
			$composer = $event->getComposer();
			$config = $composer->getConfig();
			$localRepository = $composer->getRepositoryManager()->getLocalRepository(); // https://github.com/composer/composer/issues/3425#issuecomment-63283548

			$assetsDirectory = $this->getAssetsDirectory($config);
			$this->deleteDirectory($assetsDirectory);
			$packages = $localRepository->getCanonicalPackages();

			if (empty($packages)) {
				return;
			}

			$this->createDirectory($assetsDirectory);
			$hasAssets = FALSE;

			foreach ($packages as $package) {
				$hasAssets |= $this->processPackage($package, $config, $assetsDirectory);
			}

			if (!$hasAssets) {
				$this->deleteDirectory($assetsDirectory);
			}
		}


		/**
		 * @return string
		 */
		private function getAssetsDirectory(Composer\Config $config)
		{
			$assetsDirectory = $config->get('assets-dir');

			if ($assetsDirectory === NULL) {
				$assetsDirectory = $config->get('assets-directory');
			}

			if ($assetsDirectory === NULL) {
				$assetsDirectory = 'assets';
			}

			if ($this->filesystem->isAbsolutePath($assetsDirectory)) {
				return $assetsDirectory;
			}

			return $this->filesystem->normalizePath($config->get('vendor-dir') . '/../' . $assetsDirectory);
		}


		/**
		 * @param  Composer\Package\PackageInterface
		 * @param  Composer\Config
		 * @param  string
		 * @return bool
		 */
		private function processPackage(Composer\Package\PackageInterface $package, Composer\Config $config, $assetsDirectory)
		{
			$packageName = $package->getPrettyName();
			$packageDir = $this->getPackageDirectory($package, $config);
			$packageAssetsDir = $assetsDirectory . '/' . $packageName;

			if (!is_dir($packageDir)) {
				return FALSE;
			}

			// root config
			$configAssets = $config->get('assets-files');

			if (isset($configAssets[$packageName])) {
				$this->processFiles($packageName, $packageDir, $packageAssetsDir, $configAssets[$packageName]);
				return TRUE;
			}

			// package config
			$extra = $package->getExtra();

			if (!isset($extra['assets-files'])) {
				return FALSE;
			}

			$assetsFiles = $extra['assets-files'];
			$this->processFiles($packageName, $packageDir, $packageAssetsDir, $assetsFiles);
			return TRUE;
		}


		private function getPackageDirectory(Composer\Package\PackageInterface $package, Composer\Config $config)
		{
			$vendorDir = $config->get('vendor-dir');
			$packageName = $package->getPrettyName();
			$packageTargetDir = $package->getTargetDir();
			$packageInstallDir = $packageTargetDir ? $packageName . '/' . $packageTargetDir : $packageName;
			return $this->filesystem->normalizePath(realpath($vendorDir . '/' . $packageInstallDir));
		}


		/**
		 * @param  string
		 * @param  string
		 * @param  string
		 * @param  string[]|TRUE
		 * @return void
		 */
		private function processFiles($packageName, $sourceDir, $targetDir, $files)
		{
			$this->io->write('<info>Manage assets for package ' . $packageName . '</info>');

			if ($files === TRUE) { // whole package
				$this->createSymlink($sourceDir, $targetDir);

			} elseif (is_array($files)) {
				foreach ($files as $file) {
					$this->processFile($sourceDir, $targetDir, $file);
				}

			} elseif (is_string($files)) {
				$this->processFile($sourceDir, $targetDir, $files);
			}
		}


		private function processFile($sourceDir, $targetDir, $file)
		{
			$this->io->write('  - file ' . $file);
			$this->createSymlink($sourceDir . '/' . $file, $targetDir . '/' . basename($file));
		}


		/**
		 * @param  string
		 * @param  string
		 * @return void
		 */
		private function createSymlink($source, $target)
		{
			$sourcePath = $this->filesystem->normalizePath($source);
			$targetPath = $this->filesystem->normalizePath($target);
			$sourceDirectory = dirname($sourcePath);
			$targetDirectory = dirname($targetPath);

			if (!is_dir($sourceDirectory)) {
				$this->createDirectory($sourceDirectory);
			}

			if (!is_dir($targetDirectory)) {
				$this->createDirectory($targetDirectory);
			}

			$this->filesystem->relativeSymlink($sourcePath, $targetPath);
		}


		/**
		 * @param  string
		 * @return void
		 */
		private function deleteDirectory($directory)
		{
			$this->filesystem->removeDirectory($directory);
		}


		/**
		 * @param  string
		 * @return void
		 */
		private function createDirectory($directory)
		{
			@mkdir($directory, 0777, TRUE);
		}
	}
