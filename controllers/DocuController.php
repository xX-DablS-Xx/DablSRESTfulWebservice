<?php

/**
 * Class DocuController
 *
 * This class is the api documentation controller of the Webservice module.
 * It will generate a documentation out of the method commands.
 *
 * @author Stephan Schmid (DablS)
 * @copyright Stephan Schmid (DablS) 2014+
 * @version v1.0.0
 */
class DocuController extends CController
{
	/**
	 * @var string $layout the default layout for the views
	 */
	public $layout = '//layouts/column1';

	/**
	 * @return array action filters
	 */
	public function filters()
	{
		return [ 'accessControl' ];
	}

	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	public function accessRules()
	{
		return [
			[
				'allow',
				'actions' => [ 'index' ],
				'users' => [ '@' ],
			],
			[
				'deny',
				'users' => [ '*' ],
			],
		];
	}

	/**
	 * Show the whole documentation
	 * @access public
	 * @return void
	 */
	public function actionIndex()
	{
		// required settings
		yii::import( 'webservice.controllers.*' );
		$this -> pageTitle = 'Webservice Documentation';

		// prepare all required documentation's data
		$aData = $this -> _prepareCommentData();

		// check if json is wanted
		if( !empty( $_GET['json'] ) )
		{
			$oApi = new ApiController( 'docu' );

			$oApi -> init();
			$oApi -> sendJsonResponse( $aData );
		}

		// build HTML content
		$sMethod = empty( $_GET['id'] ) ? '' : $_GET['id'];
		$sContent = $this -> _buildContent( $aData, $sMethod );

		// load script and css
		$sBaseUrl =  Yii::app() -> getBaseUrl() . DIRECTORY_SEPARATOR .'protected'. str_replace( Yii::app() -> getBasePath(), '', $this -> getModule() -> getBasePath() );

		$oClientScript = Yii::app() -> getClientScript();
		$oClientScript -> registerCssFile(  $sBaseUrl .'/css/docu.css' );

		$oClientScript -> registerScript( 'docu_ready', '

			 $( "legend" ).on( "click", function() {
				$( this ).next( ".content" ).toggle();
				var id = $( this ).parent().find( ".content" ).attr( "id" );
				if( id )
					history.pushState( id, null, "docu?id=" + id );
			} );

		', CClientScript::POS_READY );

		// write template
		$this -> render( 'index', [ 'sContent' => $sContent ] );
	}

	/**
	 * Prepares (fetch, build, ...) the documentation's data
	 * @access protected
	 * @return array
	 */
	protected function _prepareCommentData()
	{
		$aData = [];

		// include error response
		$sErrorClass = 'BaseApi';
		$sErrorMethod = '_sendErrorResponse';
		$aData['ErrorResponse'] =  $this -> _fetchComment( $sErrorClass, $sErrorMethod );

		$oApiController = new ApiController( 'error' );
		$oApiController -> init();

		$aData['ErrorResponse']['errors'] = $oApiController -> aErrorList;
		ksort( $aData['ErrorResponse']['errors'] );

		// list all rules
		$aRules = Yii::app() -> getUrlManager() -> listRules();
		$aRules = array_reverse( $aRules );
		forEach( $aRules as $oRule )
		{
			$aRoute = explode( '/', $oRule -> route );

			$sModule = ucfirst( $aRoute[0] );
			$sClass = ucfirst( $aRoute[1] ) .'Controller';
			$sMethod = 'action'. ucfirst( $aRoute[2] );

			$oController = new $sClass( 'docu' );
			$oController -> init();

			forEach( array_reverse( $oController -> getAttachedBehaviors() ) as $oBehavior )
			{
				if( $oBehavior -> getEnabled() && method_exists( $oBehavior, $sMethod ) )
					$aData[ ucfirst( $aRoute[2] ) ] = $this -> _fetchComment( $oBehavior, $sMethod );
			}

			$aData[ ucfirst( $aRoute[2] ) ] = empty( $aData[ ucfirst( $aRoute[2] ) ] ) ? [] : $aData[ ucfirst( $aRoute[2] ) ];
			$aData[ ucfirst( $aRoute[2] ) ]['pattern'] = [
				'template' => $oRule -> template,
				'params' => $oRule -> params,
				'verb' => $oRule -> verb,
			];
		}

		return $aData;
	}

	/**
	 * Fetch all information from the method comment
	 * @param object $oClass The used class instance
	 * @param string $sMethod The
	 * @access protected
	 * @return array
	 */
	protected function _fetchComment( $oClass, $sMethod )
	{
		$oReflectionMethod = new ReflectionMethod( $oClass, $sMethod );
		$sComment = str_replace( "\n\t", "\n", $oReflectionMethod -> getDocComment() );

		// parse
		$aLines = explode( "\n", $sComment );
		$aLines = array_slice( $aLines, 1, sizeof($sComment ) -2 );

		$aComment = [];
		forEach( $aLines as $sLine )
		{
			// param
			if( preg_match( '/@param/', $sLine, $aMatches ) )
				continue;

			// response method
			else if( preg_match( '/@response\s+(?<action>method)\s+(?<class>[^\s]+)\s+(?<method>[^\s]+)$/', $sLine, $aMatches ) )
			{
				$aComment['response_action'] = $aMatches;
			}
			// verbs model
			else if( preg_match( '/@verb\s+(?<action>attributes)\s+(?<class>[^\s]+)$/', $sLine, $aMatches ) )
			{
				$aComment['verb_action'] = $aMatches;
			}
			// response and verb
			else if( preg_match( '/@(?<index>(verb|response))\s+(?<type>[^\s]+)\s+(?<name>[^\s]+)\s+(?<required>(optional|required|denied))\s+(?<description>.+)$/', $sLine, $aMatches ) )
				$this -> _prepareRecursiveLines( $aComment, $aMatches) ;
			// response and verb
			else if( preg_match( '/@(?<index>(verb|response))\s+(?<type>[^\s]+)\s+(?<name>[^\s]+)\s+(?<description>.+)$/', $sLine, $aMatches ) )
				$this -> _prepareRecursiveLines( $aComment, $aMatches) ;

			// model
			else if( preg_match('/@(model|models)\s+(?<model>[^\s]+)/',$sLine, $aMatches ) )
			{
				$aModels = explode( '/', $aMatches['model'] );
				$aComment['_models'] = $this -> _addModels( empty( $aComment['_models'] ) ? [] : $aComment['_models'], $aModels );
			}
			// config model
			else if( preg_match('/@config\s+(?<path>[^\s]+)/',$sLine, $aMatches ) )
			{
				$aModels = Helper::yiiParamPath( explode( '.', $aMatches['path'] ), [] );
				$aComment['_models'] = $this -> _addModels( empty( $aComment['_models'] ) ? [] : $aComment['_models'], $aModels );
			}

			// access
			else if( preg_match('/@access\s+(?<access>[^\s]+)/',$sLine, $aMatches ) )
				$aComment['access'] = $aMatches['access'];
			// return type/description
			else if( preg_match('/@return\s+(?<type>[^\s]+)\s+(?<description>.+)/',$sLine, $aMatches ) )
			{
				$aComment['return']['type'] = $aMatches['type'];
				$aComment['return']['description'] = $aMatches['description'];
			}
			// return type
			else if( preg_match('/@return\s+(?<type>[^\s]+)/',$sLine, $aMatches ) )
			{
				$aComment['return']['type'] = $aMatches['type'];
				$aComment['return']['description'] = '';
			}
			// deprecated
			else if( preg_match( '/@deprecated\s+(?<description>.+)$/' , $sLine, $aMatches ) )
				$aComment['deprecated'] = $aMatches['description'];
			// description
			else if( preg_match('/\*\s+([^@+].*)/',$sLine, $aMatches ) )
				$aComment['description'] = ( empty( $aComment['description'] ) ? '' : $aComment['description'] .'<br />' ) . htmlentities( $aMatches[1] );

		}

		if( !empty( $aComment['verb_action'] ) )
			$this -> _prepareVerbAction( $aComment );
		unset( $aComment['verb_action'] );

		if( !empty( $aComment['response_action'] ) )
			$this -> _prepareResponseAction( $aComment );
		unset( $aComment['response_action'] );

		return $aComment;
	}

	/**
	 * Add models to model list
	 * @param array $aModels Existing model list
	 * @param array $aAddModels Models which should be added
	 * @access protected
	 * @return array
	 */
	protected function _addModels( $aModels, $aAddModels )
	{
		forEach( $aAddModels as $sModel )
		{
			if( @class_exists($sModel ) AND !in_array( $sModel, $aModels ) )
				$aModels[] = $sModel;
		}

		return $aModels;
	}

	/**
	 * Prepare and add a single recursive line, used for verb and response
	 * @param array &$aComment The whole comment list
	 * @param array $aMatches The found Line information
	 * @access protected
	 * @return void
	 */
	protected function _prepareRecursiveLines( &$aComment, $aMatches )
	{
		if( isset( $aMatches['index'] ) )
			$aMatches['name'] = $aMatches['index'] .'.'. $aMatches['name'];

		$aRecursive = explode( '.', $aMatches['name'] );
		$sName = ltrim( array_pop( $aRecursive ), '$' );

		$aParent = &$aComment;
		forEach( $aRecursive as $sParent )
		{
			if( !isset( $aParent[ $sParent ] ) )
				$aParent[ $sParent ] = [];
			$aParent = &$aParent[ $sParent ];
		}

		if( !isset( $aParent[ $sName ] ) )
		{
			$aVerb = [
				'type' => empty( $aMatches['type'] ) ? '' : $aMatches['type'],
				'required' => empty( $aMatches['required'] ) ? '' : $aMatches['required'],
				'description' => empty( $aMatches['description'] ) ? '' : $aMatches['description'],
			];
			$aParent[ $sName ] = [ '_options' => $aVerb ];
		}
	}

	/**
	 * Fetch and prepare all model verbs (properties)
	 * @param array &$aComment The whole comment list
	 * @access protected
	 * @return void
	 */
	protected function _prepareVerbAction( &$aComment )
	{
		$aModels = empty( $aComment['_models'] ) ? [] : $aComment['_models'];
		if( $aComment['verb_action']['class'] !== 'models' AND $aComment['verb_action']['class'] !== 'model' )
		{
			if( @class_exists( $aComment['verb_action']['class'] ) )
				$aModels[] = $aComment['verb_action']['class'];
		}

		if( !empty( $aModels ) )
			forEach( $aModels as $sModel )
			{
				$aComment['models'] = empty( $aComment['models'] ) ? [] : $aComment['models'];
				$aComment['models'][ $sModel ] = empty( $aComment['models'][ $sModel ] ) ? [] : $aComment['models'][ $sModel ];

				$aComment['models'][ $sModel ]['properties'] = $this -> _getModelProperties( $sModel );
			}
	}

	/**
	 * Fetch and prepare all model response (properties)
	 * @param array &$aComment The whole comment list
	 * @access protected
	 * @return void
	 */
	protected function _prepareResponseAction( &$aComment )
	{
		$aModels = empty( $aComment['_models'] ) ? [] : $aComment['_models'];
		if( $aComment['response_action']['class'] !== 'models' AND $aComment['response_action']['class'] !== 'model' )
		{
			if( @class_exists( $aComment['verb_action']['class'] ) )
				$aModels[] = $aComment['verb_action']['class'];
		}

		if( !empty( $aModels ) )
			forEach( $aModels as $sModel )
			{
				$aComment['models'] = empty( $aComment['models'] ) ? [] : $aComment['models'];
				$aComment['models'][ $sModel ] = empty( $aComment['models'][ $sModel ] ) ? [] : $aComment['models'][ $sModel ];
				$aProperties = empty( $aComment['models'][ $sModel ]['properties'] ) ? $this -> _getModelProperties( $sModel ) : $aComment['models'][ $sModel ]['properties'];

				$aComment['models'][ $sModel ]['response'] = $this -> _getModelResponse( $aProperties, $sModel, $aComment['response_action']['method'] );
			}
	}

	/**
	 * Returns a list of all model class properties
	 * @param string $sModel The name of the model class
	 * @access protected
	 * @return array
	 */
	protected function _getModelProperties( $sModel )
	{
		$oReflectionClass = new ReflectionClass( $sModel );
		$sComment = str_replace( "\n\t", "\n", $oReflectionClass -> getDocComment() );

		$aLines = explode( "\n", $sComment );
		$aLines = array_slice( $aLines, 1, sizeof($sComment ) -2 );

		$aComment = [];
		forEach( $aLines as $sLine )
		{
			if( preg_match( '/@property\s+(?<type>[^\s]+)\s+(?<name>[^\s]+)\s+(?<required>(optional|required|denied))\s+(?<description>.+)$/', $sLine, $aMatches ) )
				$this -> _prepareRecursiveLines( $aComment, $aMatches );
			else if( preg_match( '/@property\s+(?<type>[^\s]+)\s+(?<name>[^\s]+)\s*(?<description>.*)$/', $sLine, $aMatches ) )
				$this -> _prepareRecursiveLines( $aComment, $aMatches );
		}

		return $aComment;
	}

	/**
	 * Returns a list of all mode response value
	 * @param array $aProperties A list of all model class properties
	 * @param string $sModel The name of the model class
	 * @param string $sMethod A Method which limits the response
	 * @access protected
	 * @return array
	 */
	protected function _getModelResponse( $aProperties, $sModel, $sMethod )
	{
		$aReturn = [];

		$aAttributes = null;
		if( method_exists( $sModel, $sMethod ) )
		{
			$aAttributes = $sModel::model() -> $sMethod();

			$oReflectionMethod = new ReflectionMethod( $sModel, $sMethod );
			$sComment = str_replace( "\n\t", "\n", $oReflectionMethod -> getDocComment() );

			$aLines = explode( "\n", $sComment );
			$aLines = array_slice( $aLines, 1, sizeof($sComment ) -2 );

			$aComment = [];
			forEach( $aLines as $sLine )
			{
				if( preg_match( '/@(property|verb|response)\s+(?<type>[^\s]+)\s+(?<name>[^\s]+)\s+(?<required>(optional|required|denied))\s+(?<description>.+)$/', $sLine, $aMatches ) )
					$this -> _prepareRecursiveLines( $aComment, $aMatches );
				else if( preg_match( '/@(property|verb|response)\s+(?<type>[^\s]+)\s+(?<name>[^\s]+)\s*(?<description>.*)$/', $sLine, $aMatches ) )
					$this -> _prepareRecursiveLines( $aComment, $aMatches );
			}
		}

		if( $aAttributes === null )
			$aAttributes = array_keys( $sModel::model() -> getAttributes() );

		// prepare limitation
		forEach( $aAttributes as $sAttribute )
		{
			if( isset( $aComment[ $sAttribute ] ) )
				$aReturn[ $sAttribute ] = $aComment[ $sAttribute ];
			else if( isset( $aProperties[ $sAttribute ] ) )
				$aReturn[ $sAttribute ] = $aProperties[ $sAttribute ];
			else
				$aReturn[ $sAttribute ] = [];

		}

		return $aReturn;
	}

	/**
	 * Return the HTML documentation of the given Json
	 * @param array $aData A list of the whole documentation
	 * @param string $sLastMethod The last open method name
	 * @access protected
	 * @return string
	 */
	protected function _buildContent( $aData, $sLastMethod = '' )
	{
		$sContent = '';

		if( !empty( $aData ) )
		{
			forEach( $aData as $sMethod => $aMethod )
				$sContent .=   $this -> renderPartial('_docu', [ 'sMethod' => $sMethod, 'aMethod' => $aMethod, 'bHide' => ( $sLastMethod !==  $sMethod) ], true );
		}

		return $sContent;
	}

	/**
	 * Return the HTML content of a table
	 * @param array $aData A list of each table line
	 * @param array $bRequired If the required row is wanted
	 * @param integer $iDepth Sets the depth of child rows data
	 * @access public
	 * @return string
	 */
	public function buildTable( $aData, $bRequired = true, $iDepth = 0  )
	{
		$sContent = '';
		$iPadding = 15 * ( ++$iDepth );

		forEach( $aData as $sName => $aValue )
		{
			$sContent .= '<tr><td style="padding-left: '.$iPadding.'px">' . $sName . '</td>';
			if( empty( $aValue['_options'] ) )
				$sContent .= '<td></td><td></td><td></td>';
			else
			{
				$sContent .= '<td>'. ( empty( $aValue['_options']['type'] ) ? '' : $aValue['_options']['type'] ) .'</td>';
				if( $bRequired )
					$sContent .= '<td>'. ( empty( $aValue['_options']['required'] ) ? '' : $aValue['_options']['required'] ) .'</td>';
				$sContent .= '<td>'. ( empty( $aValue['_options']['description'] ) ? '' : $aValue['_options']['description'] ) .'</td>';
			}
			$sContent .= '<tr>';
			unset( $aValue['_options'] );

			$sContent .= $this -> buildTable( $aValue, $bRequired, $iDepth );
		}

		return $sContent;
	}

	/**
	 * Return the HTML content of a table
	 * @param array $aData A list of each table line
	 * @access public
	 * @return string
	 */
	public function buildErrorTable( $aData )
	{
		$sContent = '';
		$iPadding = 15;

		forEach( $aData as $iCode => $aDescription )
		{
			$sContent .= '<tr><td style="padding-left: '.$iPadding.'px">' . str_pad( $iCode, 3, '0', STR_PAD_LEFT )  . '</td>';
			$sContent .= '<td>'. ( empty( $aDescription ) ? '' : $aDescription ) .'</td>';
			$sContent .= '<tr>';
		}

		return $sContent;
	}
}
