<?php

	namespace Frontpack\ComposerAssetsPlugin;

	use Composer;


	class CommandProvider implements Composer\Plugin\Capability\CommandProvider
	{
		public function getCommands()
		{
			return array(
				new RefreshAssetsCommand,
			);
		}
	}
