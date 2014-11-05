DablSRESTfulWebservice
======================

This is a Yii Framework ( http://www.yiiframework.com ) module for a generic RESTful webservice. By default you have all basic RESTful methods, which you can add to your models.
* [__GET__]: __List__, lists all found entries (e.g.: __DOMAIN__/webservice/api/__MODEL__)
 * you can include any search criteria for your list
* [__GET__]: __Show__, shows selected entry (e.g.: __DOMAIN__/webservice/api/__MODEL__/__ID__)
* [__POST__]: __Create__, creates a new entry (e.g.: __DOMAIN__/webservice/api/__MODEL__)
 * you have to include all required values of the model
* [__PUT__]: __Update__, updates an existing entry (e.g.: __DOMAIN__/webservice/api/__MODEL__/__ID__)
 * you can include all values which you want to change
* [__DELETE__]: __Delete__, deletes an existing entry (e.g.: __DOMAIN__/webservice/api/__MODEL__/__ID__)


Versioning
----------

You can add custom webservie methods or change the basic ones via creating a new version into the __versions__ folder. There is one example given, how best practive creating a new version, into this.


Setup
-----

Clone this module, prefered as git submodule, into __application.modules__ and add the DablSWebserviceModule and the ModuleManager into the project configuration.

```
...
// autoloading model and component classes
'import' => [
	...
	'application.modules.webservice.components.DablSModuleManager'
	...
],
...
// load URLs from all modules
'onBeginRequest' => [ 'DablSModuleManager', 'collectRules' ],
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

For better human readable URLs you can add a __.htaccess__ file to your application folder.

```
RewriteEngine on

# if a directory or a file exists, use it directly
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

# otherwise forward it to index.php
RewriteRule . index.php
```


Upcomming Features
------------------
* automatically generated documentation, out of the code description
* generic secure webservice solution (encrypted)
