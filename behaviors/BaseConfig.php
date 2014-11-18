<?php

/**
 * Class BaseConfig
 *
 * This class contains generic base API methods URL rules
 * - *Show*, shows a selected model entry (GET)
 * - *List*, list all found model entries (GET)
 * - *Create*, creates a new model entry (POST)
 * - *Update*, updates an existing model entry (PUT)
 * - *Delete*, deletes an existing model entry (DELETE)
 *
 * @author Stephan Schmid (DablS)
 * @copyright Stephan Schmid (DablS) 2014+
 * @version v1.0.0
 */
class BaseConfig extends CBehavior
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

		// set default parameters (version)
		$aDefaultParameters = [
			'_version' => Yii::app() -> params['webservice']['version']
		];

		// set needed url rules for this behavior
		Yii::app() -> getUrlManager() -> addRules(
			[
				[
					'webservice/api/delete',
					'pattern' => 'webservice/api/<model:\w+>/<id:\d+>',
					'defaultParams' => $aDefaultParameters,
					'verb' => 'DELETE',
				],
				[
					'webservice/api/update',
					'pattern' => 'webservice/api/<model:\w+>/<id:\d+>',
					'defaultParams' => $aDefaultParameters,
					'verb' => 'PUT',
				],
				[
					'webservice/api/create',
					'pattern' => 'webservice/api/<model:\w+>',
					'defaultParams' => $aDefaultParameters,
					'verb' => 'POST',
				],
				[
					'webservice/api/show',
					'pattern' => 'webservice/api/<model:\w+>/<id:\d+>',
					'defaultParams' => $aDefaultParameters,
					'verb' => 'GET',
				],
				[
					'webservice/api/list',
					'pattern' => 'webservice/api/<model:\w+>',
					'defaultParams' => $aDefaultParameters,
					'verb' => 'GET',
				],
			]
		);
	}
}