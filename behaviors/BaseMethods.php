<?php

/**
 * Class BaseMethods
 *
 * This class contains generic base API methods
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
class BaseMethods extends CBehavior
{
	const ERROR_ALLOWED_MODEL 		= 21;
	const ERROR_INDEX_DATA 			= 22;
	const ERROR_MODEL_SAVE			= 23;
	const ERROR_MODEL_DELETE		= 24;

	/**
	 * Attaches the behavior object to the component
	 * @param CComponent $oOwner the component that this behavior is to be attached to
	 * @access public
	 * @return void
	 */
	public function attach( $oOwner )
	{
		parent::attach( $oOwner );

		// set error list
		$this -> owner -> aErrorList[ self::ERROR_ALLOWED_MODEL ] = 'Set model is not allowed to manipulate.';
		$this -> owner -> aErrorList[ self::ERROR_INDEX_DATA ] = 'No entry found for set index.';
		$this -> owner -> aErrorList[ self::ERROR_MODEL_SAVE ] = 'There was a problem at saving your data.';
		$this -> owner -> aErrorList[ self::ERROR_MODEL_DELETE ] = 'There was a problem at removing your data.';
	}

	/**
	 * Return entry with given index from the given model
	 * @access public
	 * @return void
	 */
	public function actionShow()
	{
		// get instance
		if( !in_array( $this -> owner -> sModel, Yii::app() -> params['webservice']['models']['show'] ) )
			$this -> owner -> _sendErrorResponse( self::ERROR_ALLOWED_MODEL );
		$oModel = new $this -> owner -> sModel;

		// fetch data
		$oModel = $oModel -> findByPk( $this -> owner -> mIndex );
		if( !$oModel )
			$this -> owner -> _sendErrorResponse( self::ERROR_INDEX_DATA );

		// get wanted response attributes
		$aAttributes = null;
		if( method_exists( $oModel, 'apiAttributes' ) )
			$aAttributes = $oModel -> apiAttributes();

		// prepare response
		$aResponse = [
			'success' => true,
			'data' => $oModel -> getAttributes( $aAttributes ),
		];

		// send response
		$this -> owner -> sendJsonResponse( $aResponse, 200 );
	}

	/**
	 * Return all data from the given model
	 * @access public
	 * @return void
	 */
	public function actionList()
	{
		// get instance
		if( !in_array( $this -> owner -> sModel, Yii::app() -> params['webservice']['models']['list'] ) )
			$this -> owner -> _sendErrorResponse( self::ERROR_ALLOWED_MODEL );
		$oModel = new $this -> owner -> sModel;

		// fetch data
		$oCriteria = new CDbCriteria;
		forEach( $this -> owner -> aData as $sName => $mValue )
		{
			if( $sName === '_order' )
				$oCriteria -> order = strval( $mValue );
			else if( $sName == '_limit' )
				$oCriteria -> limit = intval( $mValue );
			else if( $sName == '_offset' )
				$oCriteria -> offset = intval( $mValue );
			else if( $oModel -> hasAttribute( $sName ) )
				$oCriteria -> compare( $sName, str_replace( '%', '', $mValue ), ( stripos( $mValue, '%' ) === 0 ) ? true : false );
		}

		// validate offset and limit
		if( $oCriteria -> limit >= 0 AND $oCriteria -> offset >= 0 )
			$oCriteria -> offset *= $oCriteria -> limit;

		$aData = $oModel -> findAll( $oCriteria );

		// get wanted response attributes
		$aAttributes = null;
		if( method_exists( $oModel, 'apiAttributes' ) )
			$aAttributes = $oModel -> apiAttributes();

		// prepare response
		$aResponse = [ 'success' => true, 'data' => [] ];
		if( !empty( $aData ) )
			forEach( $aData as $oData )
			{
				$aResponse['data'][] = $oData -> getAttributes( $aAttributes );
			}

		// send response
		$this -> owner -> sendJsonResponse( $aResponse, 200 );
	}

	/**
	 * Create a new model entry and return it
	 * @access public
	 * @return void
	 */
	public function actionCreate()
	{
		// get instance
		if( !in_array( $this -> owner -> sModel, Yii::app() -> params['webservice']['models']['create'] ) )
			$this -> owner -> _sendErrorResponse( self::ERROR_ALLOWED_MODEL );
		$oModel = new $this -> owner -> sModel;

		// save values to model
		$this -> owner -> _saveEntry( $oModel );
	}

	/**
	 * Update an existing entry given by index and return it
	 * @access public
	 * @return void
	 */
	public function actionUpdate()
	{
		// get instance
		if( !in_array( $this -> owner -> sModel, Yii::app() -> params['webservice']['models']['update'] ) )
			$this -> owner -> _sendErrorResponse( self::ERROR_ALLOWED_MODEL );
		$oModel = new $this -> owner -> sModel;

		// fetch data
		$oModel = $oModel -> findByPk( $this -> owner -> mIndex );
		if( !$oModel )
			$this -> owner -> _sendErrorResponse( self::ERROR_INDEX_DATA );

		// save values to model
		$this -> owner -> _saveEntry( $oModel );
	}

	/**
	 * Saves given values to loaded model
	 * @param CActiveRecord $oModel The model for saving given values
	 * @access protected
	 * @return void
	 */
	protected function _saveEntry( $oModel )
	{
		// set all given values
		$oModel -> setAttributes( $this -> owner -> aData );

		// save model
		if( !$oModel -> save() )
			$this -> owner ->_sendErrorResponse( self::ERROR_MODEL_SAVE, [], 400, $oModel -> getErrors() );
		$oModel -> refresh();

		// get wanted response attributes
		$aAttributes = null;
		if( method_exists( $oModel, 'apiAttributes' ) )
			$aAttributes = $oModel -> apiAttributes();

		// prepare response
		$aResponse = [
			'success' => true,
			'data' => $oModel -> getAttributes( $aAttributes ),
		];

		// send response
		$this -> owner -> sendJsonResponse( $aResponse, 200 );
	}

	/**
	 * Delete an existing entry given by index
	 * @access public
	 * @return void
	 */
	public function actionDelete()
	{
		// get instance
		if( !in_array( $this -> owner -> sModel, Yii::app() -> params['webservice']['models']['delete'] ) )
			$this -> owner -> _sendErrorResponse( self::ERROR_ALLOWED_MODEL );
		$oModel = new $this -> owner -> sModel;

		// fetch data
		$oModel = $oModel -> findByPk( $this -> owner -> mIndex );
		if( !$oModel )
			$this -> owner -> _sendErrorResponse( self::ERROR_INDEX_DATA );

		// delete entry from model
		if( !$oModel -> delete() )
			$this -> owner ->_sendErrorResponse( self::ERROR_MODEL_DELETE, [], 400, $oModel -> getErrors() );

		// send response
		$this -> owner -> sendJsonResponse( [ 'success' => true, 'index' => $this -> owner -> mIndex ], 200 );
	}
}