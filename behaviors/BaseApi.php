<?php

/**
 * Class BaseApi
 *
 * This class contains all general API methods
 * - fetching API input data
 * - sending JSON response
 *
 * @author Stephan Schmid (DablS)
 * @copyright Stephan Schmid (DablS) 2014+
 * @version v1.0.0
 */
class BaseApi extends CBehavior
{
	const ERROR_REQUEST_METHOD 		= 11;
	const ERROR_DECODE_DATE 		= 12;

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
		$this -> owner -> aErrorList[ self::ERROR_REQUEST_METHOD ] = 'Your sent REQUEST METHOD is not allowed.';
		$this -> owner -> aErrorList[ self::ERROR_DECODE_DATE ] = 'There was a problem fetching your sent data.';
	}

	/**
	 * Returns the name of the given status code
	 * @param integer $iStatus the status code
	 * @access protected
	 * @return string
	 */
	protected function _getStatusCodeMessage( $iStatus ) {
		$aCodes = [
			200 => 'OK',
			400 => 'Bad Request',
			401 => 'Unauthorized',
			402 => 'Payment Required',
			403 => 'Forbidden',
			404 => 'Not Found',
			500 => 'Internal Server Error',
			501 => 'Not Implemented',
		];

		return isset( $aCodes[$iStatus] ) ? $aCodes[$iStatus] : '';
	}

	/**
	 * Sends the given response back to the caller
	 * @param string $sBody the response message/body
	 * @param integer $iStatus the HTTP status code
	 * @param string $sContentType the content type of the response e.g.: json,text, xml,...
	 * @access protected
	 * @return void (output)
	 */
	protected function _sendResponse( $sBody = '', $iStatus = 200, $sContentType = 'application/json' ) {
		// set the status and content type header
		$sStatusHeader = 'HTTP/1.1 '. $iStatus .' '. $this -> owner -> _getStatusCodeMessage( $iStatus );
		header( $sStatusHeader );
		header( 'Content-type: '. $sContentType );

		echo $sBody;

		Yii::app() -> end();
	}

	/**
	 * Create a device readable response for all errors
	 * @param mixed $mError The identifier of the wanted error message or an unknown error description
	 * @param array $aReplacement A list of used placeholders into the error message
	 * @param integer $iStatus The status code
	 * @param array $aDetails A list with detailed error information
	 * @access protected
	 * @return void (output)
	 *
	 * @response boolean success required If the request failed
	 * @response string error required The error message
	 * @response integer code required The error code, if it was a known error
	 * @response array details optional Detailed information of the error
	 */
	protected function _sendErrorResponse( $mError, $aReplacement = [], $iStatus = 400, $aDetails = [] )
	{
		$aResponse = [
			'success' => false,
			'error' => $mError,
			'code' => 0,
		];

		if( !empty( $aDetails ) )
			$aResponse[ 'details' ] = $aDetails;

		if( is_integer( $mError ) AND !empty( $this -> owner -> aErrorList[ $mError ] ) )
		{
			$aResponse[ 'code' ] = $mError;
			$aResponse[ 'error' ] = str_replace( array_keys( $aReplacement ), $aReplacement, $this -> owner -> aErrorList[ $mError ] );
		}

		$this -> owner -> sendJsonResponse( $aResponse, $iStatus );
	}

	/**
	 * Converts an array to a valid json response
	 * @param array $aData The data to be converted
	 * @param integer $iStatus The http status - by default 200
	 * @param integer $iCheck If the output should be run into any check
	 * @access public
	 * @return void (output)
	 */
	public function sendJsonResponse( $aData, $iStatus = 200, $iCheck = 0 ) {
		$this -> owner -> _sendResponse( json_encode( $aData, $iCheck ), $iStatus );
	}

	/**
	 * This method tries to fetch all wanted data from the stream
	 * @access protected
	 * @return void
	 */
	protected function _fetchData()
	{
		$this -> owner -> sRequestMethod = strtolower( $_SERVER['REQUEST_METHOD'] );
		if( !in_array( $this -> owner -> sRequestMethod, $this -> owner -> aRequestMethod ) )
			$this -> owner -> _sendErrorResponse( self::ERROR_REQUEST_METHOD );

		// check if there is an id given
		if( isset( $_GET['id'] ) )
		{
			$this -> owner -> mIndex = is_int( $_GET['id'] ) ? intval( $_GET['id'] ) : strval( $_GET['id'] );
			unset( $_GET['id'] );
		}
		// check if there is a model given
		if( isset( $_GET['model'] ) )
		{
			$this -> owner -> sModel = ucfirst( strval( $_GET['model'] ) );
			unset( $_GET['model'] );
		}

		// check if the body is a json and decode it
		$sContentData = file_get_contents('php://input');
		$aContentData = json_decode( $sContentData, true );
		if( !empty( $sContentData ) AND json_last_error() === JSON_ERROR_NONE )
			$this -> owner -> aData = $aContentData;
		else
		{
			// fetch raw data ata for handling
			switch( $this -> owner -> sRequestMethod )
			{
				case 'get':
					// use GET
					$this -> owner -> aData = $_GET;
					break;
				case 'post':
					// use POST
					$this -> owner -> aData = $_POST;
					break;
				case 'put':
				case 'delete':
					// get raw date for PUT and DELETE
					$aRawData = [];
					new \RawData\Stream( $aRawData, $sContentData );

					// set data and overwrite $_FILES
					$this -> owner -> aData = $aRawData[ 'post' ];
					$_FILES = $aRawData[ 'file' ];
					break;
				default:
					$this -> owner -> _sendErrorResponse( self::ERROR_DECODE_DATE );
					break;
			}
		}
	}
}