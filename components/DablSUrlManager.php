<?php

/**
 * Class DablSUrlManager
 *
 * This subclass of CUrlManager let you list your URL rules
 *
 * @author Stephan Schmid (DablS)
 * @copyright Stephan Schmid (DablS) 2014+
 * @version v1.0.0
 */
class DablSUrlManager extends CUrlManager
{

	/**
	 * @var array $_aRules The readable list of URL rules
	 */
	private $_aRules = [];

	/**
	 * Adds new URL rules.
	 * In order to make the new rules effective, this method must be called BEFORE
	 * {@link CWebApplication::processRequest}.
	 * @param array $aRules new URL rules (pattern=>route).
	 * @param boolean $bAppend whether the new URL rules should be appended to the existing ones. If false,
	 * they will be inserted at the beginning.
	 * @since 1.1.4
	 */
	public function addRules( $aRules, $bAppend = true )
	{
		parent::addRules( $aRules, $bAppend );
		
		if( $bAppend )
		{
			forEach( $aRules as $sPattern => $mRoute)
				$this->_aRules[] = $this -> createUrlRule( $mRoute, $sPattern );
		}
		else
		{
			$aRules = array_reverse( $aRules );
			forEach( $aRules as $sPattern => $mRoute )
				array_unshift( $this ->_aRules, $this -> createUrlRule( $mRoute, $sPattern ) );
		}
	}

	/**
	 * Lists all URL rules.
	 * @access public
	 * @return array
	 */
	public function listRules()
	{
		return $this -> _aRules;
	}

}
