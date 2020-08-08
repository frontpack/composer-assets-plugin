<?php

	namespace Frontpack\ComposerAssetsPlugin;


	class DefaultMapping
	{
		protected $mapping;


		public function __construct()
		{
			$this->mapping = $this->getDefaultMapping();
		}


		/**
		 * @param  string
		 * @param  string
		 * @return string[]|FALSE
		 */
		public function getFilesForPackage($packageName, $packageVersion)
		{
			if (!isset($this->mapping[$packageName])) {
				return FALSE;
			}

			foreach ($this->mapping[$packageName] as $version => $files) {
				if ($version === '*' || $version === '') {
					return $files;
				}

				$pattern = '#' . strtr(preg_quote($version, '#'), array(
					'\*' => '.*',
				)) . '#i';

				if (preg_match($pattern, $packageVersion)) {
					return $files;
				}
			}

			return FALSE;
		}


		/**
		 * @return array
		 */
		protected function getDefaultMapping()
		{
			return array(
				'bower-asset/tiny-slider' => array(
					'*' => array(
						'dist/tiny-slider.css',
						'dist/tiny-slider.js',
					),
				),

				'ckeditor/ckeditor' => array(
					'*' => TRUE,
				),

				'components/jquery' => array(
					'*' => array(
						'jquery.js'
					),
				),

				'nette/forms' => array(
					'*' => array(
						'src/assets/netteForms.js',
					),
				),

				'o5/grido' => array(
					'*' => array(
						'assets/dist',
					),
				),
			);
		}
	}
