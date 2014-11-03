<?php

/**
 * Class WebserviceModule
 *
 * This class contains all basic configuration of the webservice module.
 *
 * If you want to add a new version, you have to attach the configuration behavior file of in the init method.
 * - e.g.: $this -> attachBehavior( '__version__', '__version_config__' );
 * - all other settings can be added in the new configuration behavior file
 *
 * If you want to add more *custom* API methods, you have to attach a new method behavior file
 * - e.g.: Yii::app() -> params['webservice']['api_version']['__version_methods__'] = '__methods_behavior__'
 * - you can overwrite all existing API methods with you new methods behavior too
 *
 * @author Stephan Schmid (DablS)
 * @copyright Stephan Schmid (DablS) 2014+
 * @version v1.0.0
 */
class WebserviceModule extends CWebModule
{
	/**
	 * @param boolean $bmodulePreload If this module is prelaoded
	 */
	public $modulePreload= false;

	/**
	 * Initializes the module. This method is called at the end of the module constructor. Note that at this moment,
	 * the module has been configured, the behaviors have been attached and the application components have been registered.
	 * @access protected
	 * @return void
	 */
	protected function init()
	{
		// this method is called when the module is being created
		// you may place code here to customize the module or the application

		// set new webservice root path
		Yii::setPathOfAlias( 'webservice', dirname( __FILE__ ) );

		// import the module-level models and components
		$this->setImport(array(
			'webservice.behaviors.*',
			'webservice.components.*',
		));

		// include 3party lib \RawData\Stream
		$sIncludePathRawDataStream =  Yii::getPathOfAlias( 'application.modules.webservice.3party.RawData.Stream' );
		require_once( $sIncludePathRawDataStream. '.php' );

		// set webservice custom parameters
		Yii::app() -> params['webservice'] = [
			'version' => 'v0.0.0',
			'models' => [ 'list' => [], 'show' => [], 'create' => [], 'delete' => [] ],
			'api_versions' => [ 'base_api' => 'BaseApi', 'base_methods' => 'BaseMethods' ],
		];

		// attach behaviors (version configurations)
		$this -> attachBehaviors(
			[
				//'__version__' => '__version_config__',
				'base_config' => 'BaseConfig',
			]
		);

		parent::init();
	}
}
