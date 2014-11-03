DablSRESTfulWebservice
======================

This is a Yii Framework ( http://www.yiiframework.com ) module for a generic RESTful webservice.

By default you have all basic RESTful methods, which you can add to you models.
* [__GET__]: __List__, lists all found entries (e.g.: ___DOMAIN___/webservice/api/___MODEL___)
 * you can include any search criteria for your list
* [__GET__]: __Show__, shows selected entry (e.g.: ___DOMAIN___/webservice/api/___MODEL___/___ID___)
* [__POST__]: __Create__, creates a new entry (e.g.: ___DOMAIN___/webservice/api/___MODEL___)
 * you have to include all required values of the model
* [__PUT__]: __Update__, updates an existing entry (e.g.: ___DOMAIN___/webservice/api/___MODEL___/___ID___)
 * you can include all values which you want to change
* [__DELETE__]: __Delete__, deletes an existing entry (e.g.: ___DOMAIN___/webservice/api/___MODEL___/___ID___)


Versioning
----------

You can add custom webservie methods or change the basic ones via creating a new version into the *versions* folder. There is one example given, how best practive creating a new version, into this.


Setup
-----

Clone this module, prefered as git submodule, into *application.modules* and add the DablSWebserviceModule and the ModuleManager into the project configuration.

```
...
// load URLs from all modules
'onBeginRequest' => [ 'application.modules.DablSRESTfulWebservice.components.DablSModuleManager', 'collectRules' ],
...
// application modules
'modules' => [
  ...
	// uncomment the following to enable a webservice (api)
	'webservice' => [
		'class' => 'application.modules.DablSRESTfulWebservice.DablSWebserviceModule',
		'modulePreload' => true,
	],
	...
],
...
```


Best Practice .htaccess
-----------------------

For better human readable URLs you can add a *.htaccess* file to your application folder.

```
RewriteEngine on

# if a directory or a file exists, use it directly
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

# otherwise forward it to index.php
RewriteRule ^webservice\/(.*)$ index.php/webservice/$1
RewriteRule . index.php
```


Upcomming Features
------------------
* automatically generated documentation, out of the code description
* generic secure webservice solution (encrypted)
