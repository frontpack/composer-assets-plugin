<?php

namespace Frontpack\ComposerAssetsPlugin;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class RefreshAssetsCommand extends BaseCommand
{
    protected function configure()
    {
        $this->setName('refresh-assets');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $installer = new AssetsInstaller($this->getComposer(), $this->getIO());
        $installer->process();
    }
}
