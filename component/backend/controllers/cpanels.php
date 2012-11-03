<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsControllerCpanels extends FOFController
{
	public function execute($task) {
		if(!in_array($task, array('browse','hide2copromo'))) {
			$task = 'browse';
		}
		parent::execute($task);
	}
	
	protected function onBeforeBrowse() {
		$result = parent::onBeforeBrowse();
		
		if($result) {
			$files = array();
			$db = JFactory::getDbo();
			$tables = $db->getTableList();
			$prefix = JFactory::getConfig()->get('dbprefix', '');
			
			// check for update 2.3.0-2012-06-15
			$columnsLevels = $db->getTableColumns('#__akeebasubs_levels', true);
			if(!array_key_exists('akeebasubs_levelgroup_id', $columnsLevels)) {
				$files[] = JPATH_ADMINISTRATOR.'/components/com_akeebasubs/sql/updates/mysql/2.3.0-2012-06-15.sql';
			}
			// check for update 2.3.0-2012-06-22
			if(!in_array($prefix.'akeebasubs_invoicetemplates', $tables)) {
				$files[] = JPATH_ADMINISTRATOR.'/components/com_akeebasubs/sql/updates/mysql/2.3.0-2012-06-18.sql';
				$files[] = JPATH_ADMINISTRATOR.'/components/com_akeebasubs/sql/updates/mysql/2.3.0-2012-06-22.sql';
			}
			// check for update 2.3.0-2012-07-13
			$columns = $db->getTableColumns('#__akeebasubs_upgrades', true);
			if(!array_key_exists('combine', $columns)) {
				$files[] = JPATH_ADMINISTRATOR.'/components/com_akeebasubs/sql/updates/mysql/2.3.0-2012-07-13.sql';
			}
			// check for update 2.4.0-2012-08-14
			if(!in_array($prefix.'akeebasubs_customfields', $tables)) {
				$files[] = JPATH_ADMINISTRATOR.'/components/com_akeebasubs/sql/updates/mysql/2.4.0-2012-08-14.sql';
			}
			// check for update 2.4.5-2012-11-02
			if(!array_key_exists('params', $columnsLevels)) {
				$files[] = JPATH_ADMINISTRATOR.'/components/com_akeebasubs/sql/updates/mysql/2.4.4-2012-10-08.sql';
				$files[] = JPATH_ADMINISTRATOR.'/components/com_akeebasubs/sql/updates/mysql/2.4.5-2012-11-02.sql';
			}
			
			if(!empty($files)) foreach($files as $file) {
				$sql = JFile::read($file);
				if($sql) {
					$commands = explode(';', $sql);
					foreach($commands as $query) {
						$db->setQuery($query);
						$db->query();
					}
				}
			}
		}
		
		return $result;
	}
	
	public function hide2copromo()
	{
		// Fetch the component parameters
		$db = JFactory::getDbo();
		$sql = $db->getQuery(true)
			->select($db->qn('params'))
			->from($db->qn('#__extensions'))
			->where($db->qn('type').' = '.$db->q('component'))
			->where($db->qn('element').' = '.$db->q('com_akeebasubs'));
		$db->setQuery($sql);
		$rawparams = $db->loadResult();
		$params = new JRegistry();
		$params->loadString($rawparams, 'JSON');

		// Set the displayphpwarning parameter to 0
		$params->set('show2copromo', 0);

		// Save the component parameters
		$data = $params->toString('JSON');
		$sql = $db->getQuery(true)
			->update($db->qn('#__extensions'))
			->set($db->qn('params').' = '.$db->q($data))
			->where($db->qn('type').' = '.$db->q('component'))
			->where($db->qn('element').' = '.$db->q('com_akeebasubs'));

		$db->setQuery($sql);
		$db->query();
		
		// Redirect back to the control panel
		$url = '';
		$returnurl = FOFInput::getBase64('returnurl', '', $this->input);
		if(!empty($returnurl)) {
			$url = base64_decode($returnurl);
		}
		if(empty($url)) {
			$url = JURI::base().'index.php?option=com_akeebasubs';
		}
		$this->setRedirect($url);
	}
}