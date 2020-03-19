<?php

	namespace Frontpack\ComposerAssetsPlugin;

	use Composer;
	use Composer\Installer\PackageEvent;
	use Composer\Installer\PackageEvents;


	class AssetsInstaller
	{
		const STRATEGY_AUTO = 'auto';
		const STRATEGY_COPY = 'copy';
		const STRATEGY_SYMLINK = 'symlink';

		/** @var Composer\Composer */
		private $composer;

		/** @var Composer\IO\IOInterface */
		private $io;

		/** @var Composer\Util\Filesystem */
		private $filesystem;

		/** @var DefaultMapping */
		private $defaultMapping;


		public function __construct(
			Composer\Composer $composer,
			Composer\IO\IOInterface $io,
			Composer\Util\Filesystem $filesystem
		)
		{
			$this->composer = $composer;
			$this->io = $io;
			$this->filesystem = $filesystem;
		}


		/**
		 * @return void
		 */
		public function process()
		{
			$composer = $this->composer;
			$config = $composer->getConfig();
			$extra = $composer->getPackage()->getExtra();
			$localRepository = $composer->getRepositoryManager()->getLocalRepository(); // https://github.com/composer/composer/issues/3425#issuecomment-63283548

			$assetsDirectory = $this->getAssetsDirectory($config, $extra);
			$assetsTargets = $this->getAssetsTargets($config, $extra, $assetsDirectory);
			$directories = $this->prepareDirectories($assetsDirectory, $assetsTargets);
			$packages = $localRepository->getCanonicalPackages();

			if (empty($packages)) {
				return;
			}

			$this->createDirectory($assetsDirectory);
			$strategy = $this->getInstallStrategy($config, $extra);

			foreach ($packages as $package) {
				$packageName = $package->getPrettyName();
				$directory = $assetsDirectory;
				$targetDirectory = $assetsDirectory . '/' . $packageName;

				if (isset($assetsTargets[$packageName])) {
					$targetDirectory = $assetsTargets[$packageName];
					$directory = $targetDirectory;
				}

				$directories[$directory] |= $this->processPackage($package, $config, $extra, $targetDirectory, $strategy);
			}

			$this->removeUnusedDirectories($directories);
		}


		/**
		 * @return string
		 */
		private function getAssetsDirectory(Composer\Config $config, array $extra)
		{
			$assetsDirectory = isset($extra['assets-dir']) ? $extra['assets-dir'] : NULL;

			if ($assetsDirectory === NULL) {
				$assetsDirectory = isset($extra['assets-directory']) ? $extra['assets-directory'] : NULL;
			}

			$assetsDirectory = $config->get('assets-dir');

			if ($assetsDirectory === NULL) {
				$assetsDirectory = $config->get('assets-directory');
			}

			if ($assetsDirectory === NULL) {
				$assetsDirectory = 'assets';
			}

			if (!$this->filesystem->isAbsolutePath($assetsDirectory)) {
				$assetsDirectory = $config->get('vendor-dir') . '/../' . $assetsDirectory;
			}

			return $this->filesystem->normalizePath($assetsDirectory);
		}


		/**
		 * @return array
		 */
		private function getAssetsTargets(Composer\Config $config, array $extra, $assetsDirectory)
		{
			$assetsDirectory = rtrim($assetsDirectory, '/') . '/';
			$assetsTargets = isset($extra['assets-target']) ? $extra['assets-target'] : NULL;

			if ($assetsTargets === NULL) {
				$assetsTargets = $config->get('assets-target');
			}

			$result = array();

			if (!is_array($assetsTargets) && $assetsTargets !== NULL) {
				$this->io->writeError("<warning>Option 'assets-target' is invalid.</warning>");

			} elseif (!empty($assetsTargets)) {
				$usedDirectories = array();
				$len = strlen($assetsDirectory);

				foreach ($assetsTargets as $packageName => $assetsTarget) {
					$vendorDir = $config->get('vendor-dir');

					if (!$this->filesystem->isAbsolutePath($assetsTarget)) {
						$assetsTarget = $this->filesystem->normalizePath($vendorDir . '/../' . $assetsTarget);
					}

					if (isset($usedDirectories[$assetsTarget])) {
						throw new ConflictException("Directory '$assetsTarget' is already used for package {$usedDirectories[$assetsTarget]}.");
					}

					if (strncmp($assetsDirectory, rtrim($assetsTarget, '/') . '/', $len) === 0) {
						throw new ConflictException("Target '$assetsTarget' for package $packageName is in conflict with assets directory '$assetsDirectory'.");
					}

					$usedDirectories[$assetsDirectory] = $packageName;
					$result[$packageName] = $assetsTarget;
				}
			}

			return $result;
		}


		/**
		 * @return string
		 */
		private function getInstallStrategy(Composer\Config $config, array $extra)
		{
			$strategy = isset($extra['assets-strategy']) ? $extra['assets-strategy'] : NULL;

			if ($strategy === NULL) {
				$strategy = $config->get('assets-strategy');
			}

			if ($strategy === NULL) {
				$strategy = self::STRATEGY_AUTO;
			}

			if ($strategy === self::STRATEGY_AUTO) {
				if (Composer\Util\Platform::isWindows()) {
					$strategy = self::STRATEGY_COPY;

				} else {
					$strategy = self::STRATEGY_SYMLINK;
				}
			}

			return $strategy;
		}


		/**
		 * @param  string
		 * @param  array
		 * @return array  [directory => hasAssets]
		 */
		private function prepareDirectories($assetsDirectory, array $assetsTargets)
		{
			$directories = array();
			$directories[$assetsDirectory] = FALSE;
			$this->deleteDirectory($assetsDirectory);

			foreach ($assetsTargets as $packageName => $assetsTarget) {
				$directories[$assetsTarget] = FALSE;
				$this->deleteDirectory($assetsTarget);
			}

			return $directories;
		}


		/**
		 * @param  array
		 * @return void
		 */
		private function removeUnusedDirectories(array $directories)
		{
			foreach ($directories as $directory => $hasAssets) {
				if (!$hasAssets) {
					$this->deleteDirectory($directory);
				}
			}
		}


		/**
		 * @param  Composer\Package\PackageInterface
		 * @param  Composer\Config
		 * @param  string
		 * @param  string
		 * @return bool
		 */
		private function processPackage(Composer\Package\PackageInterface $package, Composer\Config $config, array $rootExtra, $targetDirectory, $strategy)
		{
			$packageName = $package->getPrettyName();
			$packageDir = $this->getPackageDirectory($package, $config);
			$packageAssetsDir = $targetDirectory;

			if (!is_dir($packageDir)) {
				return FALSE;
			}

			// root config
			$configAssets = isset($rootExtra['assets-files']) ? $rootExtra['assets-files'] : NULL;

			if ($configAssets === NULL) {
				$configAssets = $config->get('assets-files');
			}

			if (isset($configAssets[$packageName])) {
				$this->processFiles($packageName, $packageDir, $packageAssetsDir, $configAssets[$packageName], $strategy);
				return TRUE;
			}

			// package config
			$extra = $package->getExtra();

			if (isset($extra['assets-files'])) {
				$this->processFiles($packageName, $packageDir, $packageAssetsDir, $extra['assets-files'], $strategy);
				return TRUE;
			}

			// default config
			$defaultMapping = $this->getDefaultMapping();
			$files = $defaultMapping->getFilesForPackage($packageName, $package->getPrettyVersion());
			if ($files !== FALSE) {
				$this->processFiles($packageName, $packageDir, $packageAssetsDir, $files, $strategy);
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
		 * @param  string
		 * @return void
		 */
		private function processFiles($packageName, $sourceDir, $targetDir, $files, $strategy)
		{
			$this->io->write('<info>Manage assets for package ' . $packageName . '</info>');

			if ($files === TRUE) { // whole package
				$this->io->write('  - all files');
				$this->createCopy($sourceDir, $targetDir, $strategy);

			} elseif (is_array($files)) {
				foreach ($files as $file) {
					$this->processFile($sourceDir, $targetDir, $file, $packageName, $strategy);
				}

			} elseif (is_string($files)) {
				if (is_dir($sourceDir . '/' . $files)) {
					foreach (new \FilesystemIterator($sourceDir . '/' . $files) as $file) {
						$this->processFile($sourceDir, $targetDir, $files . '/' . $file->getBasename(), $packageName, $strategy);
					}

				} else {
					$this->processFile($sourceDir, $targetDir, $files, $packageName, $strategy);
				}
			}
		}


		/**
		 * @param  string
		 * @param  strign
		 * @param  string
		 * @param  string
		 * @param  string
		 * @return void
		 */
		private function processFile($sourceDir, $targetDir, $file, $packageName, $strategy)
		{
			$sourcePath = $sourceDir . '/' . $file;

			if (!file_exists($sourcePath)) {
				throw new FileNotFoundException("Entry '$file' not found in package '$packageName'.");
			}

			if (is_dir($sourcePath)) {
				$this->io->write('  - directory ' . rtrim($file, '/') . '/');

			} else {
				$this->io->write('  - file ' . $file);
			}

			$this->createCopy($sourcePath, $targetDir . '/' . basename($file), $strategy);
		}


		/**
		 * @param  string
		 * @param  string
		 * @param  string
		 * @return void
		 */
		private function createCopy($source, $target, $strategy)
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


			if ($strategy === self::STRATEGY_COPY) {
				$this->copy($sourcePath, $targetPath);

			} elseif ($strategy === self::STRATEGY_SYMLINK) {
				$this->filesystem->relativeSymlink($sourcePath, $targetPath);

			} else {
				throw new UnknowStategyException("Unknow copy strategy '$strategy' for file '$source'");
			}
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


		/**
		 * Copies a file or directory.
		 * @param  string
		 * @param  string
		 * @return void
		 * @throws IOException
		 * @see    https://github.com/nette/utils/blob/master/src/Utils/FileSystem.php
		 */
		public function copy($source, $dest)
		{
			if (stream_is_local($source) && !file_exists($source)) {
				throw new IOException("File or directory '$source' not found.");

			} elseif (is_dir($source)) {
				$this->createDirectory($dest);

				foreach (new \FilesystemIterator($dest) as $item) {
					$this->filesystem->remove($item->getPathname());
				}

				foreach ($iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST) as $item) {
					if ($item->isDir()) {
						$this->createDirectory($dest . '/' . $iterator->getSubPathName());

					} else {
						$this->copy($item->getPathname(), $dest . '/' . $iterator->getSubPathName());
					}
				}

			} else {
				$this->createDirectory(dirname($dest));

				if (@stream_copy_to_stream(fopen($source, 'r'), fopen($dest, 'w')) === FALSE) { // @ is escalated to exception
					throw new IOException("Unable to copy file '$source' to '$dest'.");
				}
			}
		}
	}
