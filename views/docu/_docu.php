<?php
/* @var $this DocuController */
/* @var $sMethod String */
/* @var $aMethod Array */
/* @var $bHide Boolean */

$sVerb = 'Parameter';

$sContent = '<fieldset class="main-fieldset"><legend class="main-header"> '. $sMethod .'</legend>';
$sContent .= '<div id="'. $sMethod .'" class="content" style="display:'. ( $bHide ? 'none' : 'block' ) .';">';

if( !empty( $aMethod['description'] ) )
	$sContent .= '<p class="description"><span>'. $aMethod['description'] .'</span></p>';

if( !empty( $aMethod['pattern'] ) )
{
	if( !empty( $aMethod['pattern']['template'] ) )
	{
		$sContent .= '<p><span class="main-label">URL Template:</span> '. htmlentities( $aMethod['pattern']['template'] ) .'</p>';
		if( !empty( $aMethod['pattern']['params'] ) )
		{
			$sContent .= '<ul class="template-params">';
			forEach( $aMethod['pattern']['params'] as $sParamName => $sParamValue )
				$sContent .= '<li><span class="main-label">'. $sParamName .':</span> '. $sParamValue .'</li>';
			$sContent .= '</ul>';
		}
	}

	if( !empty( $aMethod['pattern']['verb'] ) )
	{
		$sVerb = strtoupper( implode( ', ', $aMethod['pattern']['verb'] ) );
		$sContent .= '<p><span class="main-label">Verb:</span> '. $sVerb .'</p>';
	}
}

if( !empty( $aMethod['deprecated'] ) )
	$sContent .= '<p><span class="main-label">Deprecated:</span> '. $aMethod['deprecated'] .'</p>';
if( !empty( $aMethod['access'] ) )
	$sContent .= '<p><span class="main-label">Access:</span> '. $aMethod['access'] .'</p>';
if( !empty( $aMethod['return'] ) )
	$sContent .= '<p><span class="main-label">Return:</span> '. $aMethod['return']['type'] . ( empty( $aMethod['return']['description'] ) ? '': ' - '. $aMethod['return']['description'] ) .'</p>';

if( !empty( $aMethod['_models'] ) )
	$sContent .= '<p><span class="main-label">Models:</span> '. implode( ', ', $aMethod['_models'] ) .'</p>';

if( !empty( $aMethod['verb'] ) )
	$sContent .= $this -> renderPartial( '_docuTable', [ 'aData' => $aMethod['verb'], 'sType' => $sVerb ], true );
if( !empty( $aMethod['response'] ) )
	$sContent .= $this -> renderPartial( '_docuTable', [ 'aData' => $aMethod['response'], 'sType' => 'Response', 'bRequired' => false ], true );
if( !empty( $aMethod['errors'] ) )
	$sContent .= $this -> renderPartial( '_docuTable', [ 'aData' => $aMethod['errors'], 'sType' => 'Errors', 'bRequired' => false ], true );

if( !empty( $aMethod['models'] ) )
	forEach( $aMethod['models'] as $sModel => $aModel )
	{
		$sContent .= '<fieldset class="sub-fieldset"><legend class="sub-header"> '. $sModel .'</legend>';
		$sContent .= '<div class="content" style="display:none;">';

		if( !empty( $aModel['properties'] ) )
			$sContent .= $this -> renderPartial( '_docuTable', [ 'aData' => $aModel['properties'], 'sType' => $sVerb .' (Properties)' ], true );
		if( !empty( $aModel['response'] ) )
			$sContent .= $this -> renderPartial( '_docuTable', [ 'aData' => $aModel['response'], 'sType' => 'Response', 'bRequired' => false ], true );

		$sContent .= '</div></fieldset>';
	}

$sContent .= '</div></fieldset>';

// return the whole created HTML content
echo $sContent;