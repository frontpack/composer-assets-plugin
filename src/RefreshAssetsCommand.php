<?php

	namespace Frontpack\ComposerAssetsPlugin;

	use Composer;
	use Symfony\Component\Console\Input\InputInterface;
	use Symfony\Component\Console\Output\OutputInterface;


	class RefreshAssetsCommand extends Composer\Command\BaseCommand
	{
		protected function configure()
		{
			$this->setName('refresh-assets');
		}


		protected function execute(InputInterface $input, OutputInterface $output)
		{
			$installer = new AssetsInstaller($this->getComposer(), $this->getIO());
			$installer->process();
		}
	}
