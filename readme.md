
# Composer Assets Plugin

Composer plugin for installing assets.


## Installation

Use [Composer](http://getcomposer.org/):

```
composer require frontpack/composer-assets-plugin
```

Library requires PHP 5.4.0 or later.


## Configuration

### Installed packages

* `assets-files` in `extra` section
	* `true` - symlinks whole package directory
	* file path - symlinks one file
	* list of file paths - symlinks files

Example:

```
{
	"extra": {
		"assets-files": [
			'static/plugin.js', # symlinks whole package directory to "assets/package/name/plugin.js"
			'static/plugin.css', # symlinks whole package directory to "assets/package/name/plugin.css"
			'static/icons.png', # symlinks whole package directory to "assets/package/name/icons.png"
		]
	}
}
```


### Root package

* `assets-dir` - directory for installing of assets, default `assets`, relative to `vendor-dir`
* `assets-directory` - alias for `assets-dir`
* `assets-files` - list of asset files in incompatible packages, override `assets-files` in installed packages

Example:

```
{
	"config": {
		"assets-dir": "public",
		"assets-files": {
			"package/name": true, # symlinks whole package directory to "public/package/name"
			"package/name2": "js/calendar.js", # symlinks file to "public/package/name2/calendar.js"
			"package/name3": [
				'static/plugin.js', # symlinks file to "public/package/name3/plugin.js"
				'static/plugin.css', # symlinks file to "public/package/name3/plugin.css"
				'static/icons.png', # symlinks file to "public/package/name3/icons.png"
			]
		}
	}
}
```


------------------------------

License: [New BSD License](license.md)
<br>Author: Jan Pecha, https://www.janpecha.cz/
