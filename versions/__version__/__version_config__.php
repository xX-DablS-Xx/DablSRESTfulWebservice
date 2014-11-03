<?php

/**
 * Class __version_config__
 *
 * Version description text ...
 *
 * @author Stephan Schmid (DablS)
 * @copyright Stephan Schmid (DablS) 2014+
 * @version v_._._
 */
class __version_config__ extends CBehavior
{
	/**
	 * Attaches the behavior object to the component
	 * @param CComponent $oOwner the component that this behavior is to be attached to
	 * @access public
	 * @return void
	 */
	public function attach( $oOwner )
	{
		parent::attach( $oOwner );

		// set new webservice configuration
		$aParams = [
			'webservice' => [
				'version' => 'v_._._',
				'api_versions' => [ '__version_methods__' => '__methods_behavior__' ],
				'models' => [
					'list' => [ /* 'model', ... */ ],
					'show' => [ /* 'model', ... */ ],
					'create' => [ /* 'model', ... */ ],
					'delete' => [ /* 'model', ... */ ],
				],
			]
		];

		// merge webservice configuration
		Yii::app() -> params -> mergeWith( $aParams, true );

		/**
		 * Here you can enter you custom API configuration
		 */
	}
}