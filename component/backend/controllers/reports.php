<?php

/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */
class AkeebasubsControllerReports extends F0FController
{
	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->registerTask('getexpirations', 'browse');
		$this->registerTask('renewals', 'browse');
		$this->registerTask('vies', 'invoices');
		$this->registerTask('vatmoss', 'invoices');

		$this->cacheableTasks = array();
	}

	public function invoices()
	{
		$this->layout = 'invoices';

		// Get the display parameters
		$params = $this->getInvoiceListParameters();

		$db = JFactory::getDbo();

		$query = $db->getQuery(true)
			->select(array(
				'akinv.invoice_date', 'akinv.display_number as number',
				'aksub.net_amount', 'aksub.tax_amount', 'aksub.gross_amount',
				'aksub.tax_percent',
				'akuser.isbusiness', 'akuser.businessname', 'akuser.occupation',
				'akuser.vatnumber',
				'akuser.viesregistered', 'akuser.address1', 'akuser.address2',
				'akuser.city', 'akuser.state', 'akuser.zip', 'akuser.country',
				'aksub.processor', 'aksub.processor_key', 'usr.id', 'usr.name',
				'usr.username', 'usr.email'
			))
			->from($db->qn('#__akeebasubs_invoices') . ' AS ' . $db->qn('akinv'))
			->innerJoin(
				$db->qn('#__akeebasubs_subscriptions') . ' AS ' . $db->qn('aksub') .
				' ON (' . $db->qn('aksub') . '.' . $db->qn('akeebasubs_subscription_id') . ' = ' .
				$db->qn('akinv') . '.' . $db->qn('akeebasubs_subscription_id') . ')'
			)
			->innerJoin(
				$db->qn('#__users') . ' AS ' . $db->qn('usr') .
				' ON (' . $db->qn('usr') . '.' . $db->qn('id') . ' = ' .
				$db->qn('aksub') . '.' . $db->qn('user_id') . ')'
			)
			->join(
				'LEFT OUTER',
				$db->qn('#__akeebasubs_users') . ' AS ' . $db->qn('akuser') .
				' ON (' . $db->qn('akuser') . '.' . $db->qn('user_id') . ' = ' .
				$db->qn('aksub') . '.' . $db->qn('user_id') . ')'
			)
			->where('YEAR(' . $db->qn('akinv') . '.' . $db->qn('invoice_date') . ') =' . $db->q($params['year']))
			->where('MONTH(' . $db->qn('akinv') . '.' . $db->qn('invoice_date') . ') =' . $db->q($params['month']))
			->where($db->qn('akinv') . '.' . $db->qn('extension') . ' = ' . $db->q($params['extension']));

		if ($params['vies'])
		{
			$this->layout = 'invoices_vies';
			$query->where($db->qn('akuser') . '.' . $db->qn('viesregistered') . ' = ' . $db->q(1));
		}

		if ($params['vatmoss'])
		{
			$this->layout = 'invoices_vatmoss';
			$query->where($db->qn('akuser') . '.' . $db->qn('viesregistered') . ' = ' . $db->q(0));
			$query->where($db->qn('aksub') . '.' . $db->qn('tax_amount') . ' > ' . $db->q(0.01));
			$query->order($db->qn('akuser') . '.' . $db->qn('country') . ' ASC');
		}

		$db->setQuery($query);
		$records = $db->loadObjectList();

		// Assign the records and the layout to the view
		$view = $this->getThisView();
		$view->records = $records;
		$view->params = $params;

		// Show the view
		$this->display(false);
	}

	private function getInvoiceListParameters()
	{
		JLoader::import('joomla.utilities.date');
		$jNow = new JDate();

		$month = $this->input->getInt('month', 0);

		if (($month < 1) || ($month > 12))
		{
			$month = (int)$jNow->format('m');
			$month--;
		}

		$year = $this->input->getInt('year', 0);

		if (($year < 2010) || ($year > 2100))
		{
			$year = (int)$jNow->format('Y');
		}

		if ($month <= 0)
		{
			$month = 12;
			$year--;
		}

		$vies = false;
		$vatmoss = false;

		switch ($this->getTask())
		{
			case 'vies':
				$vies = true;
				break;

			case 'vatmoss':
				$vatmoss = true;
				break;
		}

		$invoiceExtension = $this->input->getCmd('extension', 'akeebasubs');

		return array(
			'month'     => $month,
			'year'      => $year,
			'vies'      => $vies,
			'vatmoss'   => $vatmoss,
			'extension' => $invoiceExtension,
		);
	}
}