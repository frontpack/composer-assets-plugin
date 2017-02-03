<?php

	namespace Frontpack\ComposerAssetsPlugin;

	use Composer;
	use Composer\Installer\PackageEvent;
	use Composer\Installer\PackageEvents;


	class AssetsInstaller
	{
		/** @var Composer\Composer */
		private $composer;

		/** @var Composer\IO\IOInterface */
		private $io;

		/** @var Composer\Util\Filesystem */
		private $filesystem;

		/** @var DefaultMapping */
		private $defaultMapping;


		public function __construct(Composer\Composer $composer, Composer\IO\IOInterface $io)
		{
			$this->composer = $composer;
			$this->io = $io;
			$this->filesystem = new Composer\Util\Filesystem;
		}


		/**
		 * @return void
		 */
		public function process()
		{
			$composer = $this->composer;
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

			if (isset($extra['assets-files'])) {
				$this->processFiles($packageName, $packageDir, $packageAssetsDir, $extra['assets-files']);
				return TRUE;
			}

			// default config
			$defaultMapping = $this->getDefaultMapping();
			$files = $defaultMapping->getFilesForPackage($packageName, $package->getPrettyVersion());
			if ($files !== FALSE) {
				$this->processFiles($packageName, $packageDir, $packageAssetsDir, $files);
				return TRUE;
			}

			// no files
			return FALSE;
		}


		/**
		 * @return DefaultMapping
		 */
		private function getDefaultMapping()
		{
			if (!isset($this->defaultMapping)) {
				$this->defaultMapping = new DefaultMapping;
			}
			return $this->defaultMapping;
		}


		/**
		 * @return string
		 */
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
					$this->processFile($sourceDir, $targetDir, $file, $packageName);
				}

			} elseif (is_string($files)) {
				$this->processFile($sourceDir, $targetDir, $files, $packageName);
			}
		}


		/**
		 * @param  string
		 * @param  strign
		 * @param  string
		 * @param  string
		 * @return void
		 */
		private function processFile($sourceDir, $targetDir, $file, $packageName)
		{
			$sourcePath = $sourceDir . '/' . $file;

			if (!file_exists($sourcePath)) {
				throw new FileNotFoundException("Entry '$file' not found in package '$packageName'.");
			}

			if (is_dir($sourcePath)) {
				$this->io->write('  - directory ' . $file . '/');

			} else {
				$this->io->write('  - file ' . $file);
			}

			$this->createSymlink($sourcePath, $targetDir . '/' . basename($file));
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
