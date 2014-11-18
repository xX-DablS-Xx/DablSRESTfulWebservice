<?php
/* @var $this DocuController */
/* @var $aData Array */
/* @var $sType String */
/* @var $bRequired Boolean */

$bRequired = isset( $bRequired ) ? $bRequired : true;

$sContent = '<fieldset class="table-fieldset"><legend class="table-legend">> ' . $sType . '</legend>';
$sContent .= '<div class="content" style="display:none;">';
$sContent .= '<table cellspacing="0" cellpadding="0">';

$sContent .= '<!-- Table Header --><thead><tr>'. ( strtolower( $sType ) === 'errors' ? '<th>Code</th>' : '<th>Name</th><th>Type</th>' ) . ( $bRequired ? '<th>Required</th>' : '' ) .'<th>Description</th></tr></thead>';
$sContent .= '<!-- Table Body --><tbody>';

if( strtolower( $sType ) === 'errors' )
	$sContent .= $this -> buildErrorTable( $aData );
else
	$sContent .= $this -> buildTable( $aData, $bRequired );

$sContent .= '</tbody></table></div></fieldset>';

// return the whole created HTML content
echo $sContent;