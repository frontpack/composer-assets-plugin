<?php

namespace Frontpack\ComposerAssetsPlugin;

class DefaultMapping
{
    /** @var array $mapping */
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
            return false;
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

        return false;
    }


    /**
     * @return array
     */
    protected function getDefaultMapping()
    {
        return array(
            'ckeditor/ckeditor' => array(
                '*' => true,
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
