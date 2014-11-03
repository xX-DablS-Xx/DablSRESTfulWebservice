<?php

/**
 * Class DablSModuleManager
 *
 * This class goes through each modules and initialize it.
 * If there are any URL rules into the modules, it tries to add them.
 *
 * @author Stephan Schmid (DablS)
 * @copyright Stephan Schmid (DablS) 2014+
 * @version v1.0.0
 */
class DablSModuleManager
{
	/**
	 * Initialize each module and collect all URL rules
	 * @access public static
	 * @return boolean (true)
	 */
	public static function collectRules()
	{
		if( !empty( Yii::app() -> modules ) )
		{
			forEach( Yii::app() -> modules as $sModuleName => $aConfig )
			{
				// check if the module is configured to preload URL rules
				if( isset( $aConfig['modulePreload'] ) AND $aConfig['modulePreload'] === true )
				{
					$oModule = Yii::app() -> getModule( $sModuleName );
					if( !empty( $oModule -> aUrlRules ) )
						Yii::app() -> getUrlManager() -> addRules( $oModule -> aUrlRules );
				}
			}
		}

		return true;
	}
}
