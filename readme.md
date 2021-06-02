# Composer Assets Plugin

Composer plugin for installing assets.


Support Me
----------

Do you like Composer Assets Plugin? Are you looking forward to the **new features**?

<a href="https://www.paypal.com/donate?hosted_button_id=A3VQYFGCF99FU"><img src="https://buymecoffee.intm.org/img/composer-assets-plugin-paypal-donate@2x.png" alt="PayPal or credit/debit card" width="254" height="248"></a>

<img src="https://buymecoffee.intm.org/img/bitcoin@2x.png" alt="Bitcoin" height="32"> `bc1qvhp0awxa54hndxfjctwyrs27q5ryy4337vlwsd`

Thank you!


## Installation

Use [Composer](http://getcomposer.org/):

```
composer require frontpack/composer-assets-plugin
```

Library requires PHP 5.6.0 or later.


## Commands

* `composer refresh-assets` - refresh files in `assets` directory


## Assets configuration

### Packages

* `assets-files` in section `extra`
	* `true` - symlinks whole package directory
	* file path - symlinks one file or content of directory
	* list of file paths - symlinks files/directories

Example:

``` json
{
	"extra": {
		"assets-files": [
			"static/plugin.js",
			"static/plugin.css",
			"static/icons.png"
		]
	}
}
```

* `static/plugin.js` - symlinks file to `assets/org/package/plugin.js`
* `static/plugin.css` - symlinks file to `assets/org/package/plugin.css`
* `static/icons.png` - symlinks file to `assets/org/package/icons.png`

Or you can use simple:

``` json
{
	"extra": {
		"assets-files": "static"
	}
}
```

with same result.


### Root package

* `assets-dir` - directory for installing of assets, default `assets`, relative to `vendor-dir`
* `assets-directory` - alias for `assets-dir`
* `assets-files` - list of asset files in incompatible packages, it overrides `assets-files` from installed packages
* `assets-strategy` - install strategy for assets
	* `auto` - select strategy by platform (default value)
	* `copy` - copy all assets, default strategy on Windows
	* `symlink` - create relative symlinks, default strategy on non-Windows platforms
* `assets-target` - target directory for specific packages, relative to `vendor-dir`, must be out of `assets-dir`

Example:

``` json
{
	"extra": {
		"assets-dir": "public",
		"assets-files": {
			"org/package": true,
			"org/package2": "js/calendar.js",
			"org/package3": [
				"static/plugin.js",
				"static/plugin.css",
				"static/icons.png"
			]
		},
		"assets-target": {
			"ckeditor/ckeditor": "admin/wysiwyg"
		}
	}
}
```

* `org/package` - symlinks whole package directory to `public/org/package`
* `org/package2` - symlinks file `js/calendar.js` to `public/org/package2/calendar.js`
* `org/package3`
	* `static/plugin.js` - symlinks file to `public/org/package3/plugin.js`
	* `static/plugin.css` - symlinks file to `public/org/package3/plugin.css`
	* `static/icons.png` - symlinks file to `public/org/package3/icons.png`
* `ckeditor/ckeditor` - symlinks files to `admin/wysiwyg`


## Default mapping

Plugin provides default mapping for selected incompatible packages. You can override this mapping in your `composer.json`.

List of packages with default mapping:

* `bower-asset/tiny-slider`
* `ckeditor/ckeditor`
* `components/jquery`
* `enyo/dropzone`
* `nette/forms`
* `o5/grido`


## Where find supported packages?

Some libraries and packages support Composer by default. For other exists shim-repositories:

* https://github.com/components
* https://github.com/frontpack

Always you can search packages on [Packagist](https://packagist.org/).

------------------------------

License: [New BSD License](license.md)
<br>Author: Jan Pecha, https://www.janpecha.cz/
