<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Mike Mitterer <office@bitcon.at>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Plugin 'MM Birthday-Greeting' for the 'mm_birthdaygreeting' extension.
 *
 * @author	Mike Mitterer <office@bitcon.at>
 */


require_once(t3lib_extMgm::extPath('mm_bccmsbase').'lib/class.mmlib_extfrontend.php');


class tx_mmbirthdaygreeting_pi1 extends mmlib_extfrontend {
	var $prefixId = 'tx_mmbirthdaygreeting_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_mmbirthdaygreeting_pi1.php';	// Path to this script relative to the extension dir.
	var $pi_checkCHash = TRUE;
	
	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content,$conf)	{
		$conf = $this->initPlugin($conf);
		
		// Ask for the current User - if no user is logged in we ask for a uid which ist -1
		// With -1 nothing will be found
		$strWhereStatement = 'uid=' . $this->getCurrentFEUID();
		
		// Get number of records:
		$res = $this->execQuery(1,$strWhereStatement);
		if($res == false) return $this->pi_getLL('error_emty_query','');
		
		list($this->internal['res_count']) = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
		if($this->internal['res_count'] == 0) return $this->pi_getLL('error_emty_query','');
		
		// Make listing query, pass query to SQL database:
		$res = $this->execQuery(0,$strWhereStatement);
		if($res == false) return $this->pi_getLL('error_emty_query','');
		
		// Init the currentRow
		$this->internal['currentRow'] = $this->_fetchData($res);
		//t3lib_div::debug($this->internal['currentRow'],1);
		
		// dummy field will be set 
		$this->conf['typodbfield.']['message.']['value'] = $this->getBDAYMessage();
		$this->conf['typodbfield.']['abs_days_til_bday.']['value'] = abs($this->internal['currentRow']['days_til_bday']);
		$this->conf['typodbfield.']['cur_age_plus_one.']['value'] = abs($this->internal['currentRow']['age']) + 1;
		$this->conf['typodbfield.']['cur_age_minus_one.']['value'] = abs($this->internal['currentRow']['age']) - 1;
		if($this->getBDAYMessage() == '') return $this->pi_getLL('error_emty_query',''); 
		
		$this->setViewType('singleView');
		$content .= $this->getContentForView();
		
		return $this->pi_wrapInBaseClass($content);
		}
	
	/**
	 * Do some initialisation work
	 *
	 * @param	[array]			$conf:TS Configuration for this plugin
	 *
	 * @return	[array]			returns the configuration-Array
	 */
	function initPlugin($conf) {
		$aInitData['tablename'] 	= 'fe_users';
		$aInitData['uploadfolder'] 	= 'tx_mmbirthdaygreeting';
		$aInitData['extensionkey'] 	= 'mm_birthdaygreeting';
		
		// Optional
		$aInitData['flex2conf'] 		= $this->getFLEXConversionInfo();

		$conf = $this->initFromArray($conf,$aInitData);
		
		//$this->cattree = t3lib_div::makeInstance('mmlib_tree');
		//$this->cattree->init($this);
		
		return $conf;	
		}	

	/**
	 * The array ties together the static-TS configuration and the Flex Form
	 * If data is set in both areas then FlexForm settings have the priority.
	 *
	 * @return	[array]	- Information about the TS2Flex connection
	 */	
	function getFLEXConversionInfo()	
		{
		return array(
		
			'days_before'				=> 'sMAIN:days_before',
			'days_after'				=> 'sMAIN:days_before',
	
			'message_before'			=> 'sMESSAGE_BEFORE:message_before',
			'message_birthday'			=> 'sMESSAGE_BIRTHDAY:message_birthday',
			'message_after'				=> 'sMESSAGE_AFTER:message_after',
	
			'show_message_before'		=> 'sMAIN:show_message_before',
			'show_message_after'		=> 'sMAIN:show_message_after',
			);		
		}		
		
	/**
	 * Executes the main-query from the ListView.
	 * Select all the Files from a specific Kategory ($this->piVars['mode'])
	 * 
	 * @param	[boolean]		$fCountRecords: Just count the records
	 * @param	[string]		$strWhereStatement: Optional additional WHERE clauses put in the end of the query
	 *
	 * @return	[integer]	pointer MySQL result pointer / DBAL object
	 */	
	function execQuery($fCountRecords = 0,$strWhereStatement = '')
		{
		$days_before 	= $this->conf['days_before'];
		$days_after 	= $this->conf['days_after'];
		$format			= $this->conf['birthday_date_format'];
		
		/*
		SELECT 
			    YEAR(CURDATE()) as year,
			    DAYOFYEAR(STR_TO_DATE(tx_mmbirthdaygreeting_birthday,'%d.%m.%Y')) as doj,
			    MONTH(STR_TO_DATE(tx_mmbirthdaygreeting_birthday,'%d.%m.%Y')) as month,
			    DAY(STR_TO_DATE(tx_mmbirthdaygreeting_birthday,'%d.%m.%Y')) as day,
			    STR_TO_DATE(tx_mmbirthdaygreeting_birthday,'%d.%m.%Y') as birthday,
			    makedate(YEAR(CURDATE()),DAYOFYEAR(STR_TO_DATE(tx_mmbirthdaygreeting_birthday,'%d.%m.%Y'))) as this_year_birthday,
			    datediff(
			        CURDATE(),
			        makedate(YEAR(CURDATE()),DAYOFYEAR(STR_TO_DATE(tx_mmbirthdaygreeting_birthday,'%d.%m.%Y')))
			        ) as days_til_bday,
			    CURDATE() as today,
                CONVERT(DATE_FORMAT(
                	FROM_DAYS(
                    	TO_DAYS(NOW())
                        -
                        TO_DAYS(STR_TO_DATE(tx_mmbirthdaygreeting_birthday,'%d.%m.%Y')) + 1
                        )
                    ,'%Y') + 1,UNSIGNED) as age,
			    uid,
                name
			from fe_users
			where
			    datediff(
			        CURDATE(),
			        makedate(YEAR(CURDATE()),DAYOFYEAR(STR_TO_DATE(tx_mmbirthdaygreeting_birthday,'%d.%m.%Y')))
			        ) < 20 
			AND
			    datediff(
			        CURDATE(),
			        makedate(YEAR(CURDATE()),DAYOFYEAR(STR_TO_DATE(tx_mmbirthdaygreeting_birthday,'%d.%m.%Y')))
			        ) > -20
		*/
		
		$SQL['select'] 	= "YEAR(CURDATE()) as year,
						    DAYOFYEAR(STR_TO_DATE(tx_mmbirthdaygreeting_birthday,'$format')) as doj,
						    MONTH(STR_TO_DATE(tx_mmbirthdaygreeting_birthday,'$format')) as month,
						    DAY(STR_TO_DATE(tx_mmbirthdaygreeting_birthday,'$format')) as day,
			    			STR_TO_DATE(tx_mmbirthdaygreeting_birthday,'$format') as birthday,
						    makedate(YEAR(CURDATE()),DAYOFYEAR(STR_TO_DATE(tx_mmbirthdaygreeting_birthday,'$format'))) as this_year_birthday,
						    datediff(
						        CURDATE(),
						        makedate(YEAR(CURDATE()),DAYOFYEAR(STR_TO_DATE(tx_mmbirthdaygreeting_birthday,'$format')))
						        ) as days_til_bday,
			                CONVERT(DATE_FORMAT(
			                	FROM_DAYS(
			                    	TO_DAYS(NOW())
			                        -
			                        TO_DAYS(STR_TO_DATE(tx_mmbirthdaygreeting_birthday,'$format')) + 1
			                        )
			                    ,'%Y'),UNSIGNED) as age,
						    CURDATE() as today,
						    uid,
						    name";
		
		$SQL['from']	= 'fe_users';
		$SQL['order_by']= ''; // Defaultsettings are made in setup.txt (order)
		$SQL['group_by']= '';
    	$SQL['limit']	= '';
    	
		$WHERE['enable_fields']	= $this->cObj->enableFields($this->getTableName()) . ' ';
    	$WHERE['datediff'] = "
    				datediff(
    					CURDATE(),
    					MAKEDATE(YEAR(CURDATE()),DAYOFYEAR(STR_TO_DATE(tx_mmbirthdaygreeting_birthday,'$format')))
    					) < '$days_before' 
    				AND 
    				datediff(
    					CURDATE(),
    					MAKEDATE(YEAR(CURDATE()),DAYOFYEAR(STR_TO_DATE(tx_mmbirthdaygreeting_birthday,'$format')))
    					) > '-$days_after'";
    	
		$WHERE['statement']		= $strWhereStatement;
			
		$SQL['where']	= $this->implodeWithoutBlankPiece('AND ',$WHERE);
    	
    	
		if($fCountRecords == true) {
			$SQL['select'] 		= 'count(*)';
			$SQL['limit']		= '';
		}

		//$GLOBALS['TYPO3_DB']->debugOutput = true;
		//$GLOBALS['TYPO3_DB']->store_lastBuiltQuery = true;
		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			$SQL['select'],
			$SQL['from'],
			$SQL['where'],             
			$SQL['group_by'],
			$SQL['order_by'],
			$SQL['limit']
			);	

		
		// Only for debugging...
		if(!$res)
			{
			t3lib_div::debug('----------- SQL Statement ---------------',1);
			t3lib_div::debug(mysql_errno(),"mysql_errno()=");
			t3lib_div::debug(mysql_error(),"mysql_error()=");
			t3lib_div::debug($SQL,"\$SQL=");
			t3lib_div::debug($GLOBALS['TYPO3_DB']->debug_lastBuiltQuery,"lastBuiltQuery=");
			t3lib_div::debug('++++++++++++++++++++++++++++++++++++++++++',1);
			}
			
		//t3lib_div::debug($GLOBALS['TYPO3_DB']->debug_lastBuiltQuery,"lastBuiltQuery=");
			
		return $res;		
		}

	/**
	 * Returns the right birthday-message. The available fields will be
	 * replaced by their value
	*/
	function getBDAYMessage() {
		$tabledata 		= $this->internal['currentRow'];
		$messages[0] 	= $this->conf['message_before'];
		$messages[1] 	= $this->conf['message_birthday'];
		$messages[2] 	= $this->conf['message_after'];
		
		$message = '';
		if($tabledata['days_til_bday'] < 0 && $this->conf['show_message_before'] == 1)  {
			$message = $messages[0];
		} else if($tabledata['days_til_bday'] > 0 && $this->conf['show_message_after'] == 1) {
			$message = $messages[2];
		} else if($tabledata['days_til_bday'] == 0) { 
			$message = $messages[1];
		}
		
		if($message == '') return $message;
		
		//t3lib_div::debug($tabledata,1);
		
		foreach($tabledata as $key=>$value) {
			$key = strtoupper($key);
			$message = str_replace('###' . $key . '###',$value,$message);
		//t3lib_div::debug($message,1);
		}
		
		return $message;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mm_birthdaygreeting/pi1/class.tx_mmbirthdaygreeting_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mm_birthdaygreeting/pi1/class.tx_mmbirthdaygreeting_pi1.php']);
}

?>