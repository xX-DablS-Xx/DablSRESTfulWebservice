<?php

/**
 * Class ApiController
 *
 * This class is the api main controller of the Webservice module.
 * All api requests should pass this controller, before executing.
 *
 * @author Stephan Schmid (DablS)
 * @copyright Stephan Schmid (DablS) 2014+
 * @version v1.0.0
 */
class ApiController extends CController
{

	const ERROR_MISSING_ACTION 		= 1;
	const ERROR_DENIED_ACTION 		= 2;

	/**
	 * @var array $aErrorList A List which contains all error messages
	 */
	public $aErrorList = [
		self::ERROR_MISSING_ACTION => 'Requested action "{{action}}" does not exist.',
		self::ERROR_DENIED_ACTION => 'Requested action "{{action}}" is denied.',
	];

	/**
	 * @var mixed $mIndex The index of the Model
	 * @var string $sModel The name of the Model class
	 * @var array $aData A list of all given data / information
	 * @var string $sActionVersion The version of the requested action
	 * @var string $sRequestMethod The used server request method
	 * @var array $aRequestMethod A list of all allowed server request methods
	 * @var array $_aBehaviors A list of all attached behaviors
	 */
	public $mIndex = 0;
	public $sModel = '';
	public $aData = [];
	public $sActionVersion = '';
	public $sRequestMethod = '';
	public $aRequestMethod = [ 'get', 'post', 'put', 'delete' ];
	protected $_aBehaviors = [];

	/**
	 * Initializes the controller. This method is called by the application before the controller starts to execute
	 * @access public
	 * @return void
	 */
	public function init()
	{
		// attach behaviors (api methods) reversed!
		$this -> attachBehaviors( array_reverse( Yii::app() -> params['webservice']['api_versions'] ) );
	}

	/**
	 * Returns the filter configurations.
	 * @access public
	 * @return array
	 */
	public function filters()
	{
		return [
			'accessControl',
		];
	}

	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @access public
	 * @return array
	 */
	public function accessRules()
	{
		// check if there is an version given
		if( isset( $_GET['_version'] ) )
		{
			$this -> sActionVersion = strval( $_GET['_version'] );
			unset( $_GET['_version'] );
		}

		return [
			[
				'allow',
				'expression' => function()
				{
					return !empty( $this -> sActionVersion );
				},
			],
			[
				'deny',
				'deniedCallback' => function( $oAccessRule )
				{
					$aReplacement = [ '{{action}}' => $this -> getAction() -> getId() ];
					$this -> _sendErrorResponse( self::ERROR_DENIED_ACTION, $aReplacement );
				},
			],
		];
	}

	/**
	 * Creates the action instance based on the action name
	 * @param string $sAction ID of the action
	 * @access public
	 * @return CAction
	 */
	public function createAction( $sAction )
	{
		if( ( $oAction = parent::createAction( $sAction ) ) !== null )
			return $oAction;

		forEach( $this -> getAttachedBehaviors() as $oBehavior )
		{
			if( $oBehavior -> getEnabled() && method_exists( $oBehavior, 'action'. $sAction ) )
				return new CInlineAction( $oBehavior, $sAction );
		}

		return null;
	}

	/**
	 * Attaches a behavior to this component
	 * @param string $sName The behavior's name. It should uniquely identify this behavior
	 * @param mixed $mBehavior The behavior configuration
	 * @access public
	 * @return CBehavior
	 */
	public function attachBehavior( $sName, $mBehavior )
	{
		return $this -> _aBehaviors[ $sName ] = parent::attachBehavior( $sName, $mBehavior );
	}

	/**
	 * Detaches a behavior from the component
	 * @param string $sName
	 * @access public
	 * @return CBehavior
	 */
	public function detachBehavior( $sName )
	{
		$oReturn = parent::detachBehavior( $sName );
		unset( $this->_aBehaviors[ $sName ] );

		return $oReturn;
	}

	/**
	 * Returns a List of all attached behaviors
	 * @access public
	 * @return array
	 */
	public function getAttachedBehaviors()
	{
		return $this -> _aBehaviors;
	}

	/**
	 * This method is invoked right before an action is to be executed (after all possible filters.)
	 * @param CAction $action the action to be executed
	 * @access protected
	 * @return boolean / void (error)
	 */
	protected function beforeAction( $oAction )
	{
		if( parent::beforeAction( $oAction ) )
		{
			// fetch data
			$this -> _fetchData();

			// validate data
			if( method_exists( $this, '_validateData' ) )
				return $this -> $this -> _validateData();

			return true;
		}
		return false;
	}

	/**
	 * Create a device readable response for wrong actions
	 * @param string $sAction the given action text
	 * @access public
	 * @return void (output)
	 */
	public function missingAction( $sAction )
	{
		$aReplacement = [ '{{action}}' => $sAction ];
		$this -> _sendErrorResponse( self::ERROR_MISSING_ACTION, $aReplacement );
	}
}