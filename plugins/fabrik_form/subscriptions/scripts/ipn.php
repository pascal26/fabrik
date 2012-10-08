<?php

/**
 * Optional script for extending the Fabrik PayPal form plugin
 * @package fabrikar
 * @author Hugh Messenger
 * @copyright (C) Hugh Messenger
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

JTable::addIncludePath(JPATH_SITE . '/plugins/fabrik_form/subscriptions/tables');

class fabrikSubscriptionsIPN
{
	function __construct()
	{
		// Include the JLog class.
		jimport('joomla.log.log');

		// Add the logger.
		JLog::addLogger(array('logger' => 'database'));

	}

	/**
	 * Completed payment
	 *
	 * @param   object  $listModel  list model
	 * @param   array   $request    request data
	 * @param   array   &$set_list  new invoice properties
	 * @param   array   &$err_msg   error message
	 *
	 * @return	mixed false or 'ok'
	 */

	public function payment_status_Completed($listModel, $request, &$set_list, &$err_msg)
	{
		$this->log('fabrik.ipn.payment_status_Completed', '');
		return 'ok';
	}

	/**
	 * Pending payment
	 *
	 * @param   object  $listModel  list model
	 * @param   array   $request    request data
	 * @param   array   &$set_list  new invoice properties
	 * @param   array   &$err_msg   error message
	 *
	 * @return	mixed false or 'ok'
	 */

	public function payment_status_Pending($listModel, $request, &$set_list, &$err_msg)
	{
		$this->log('fabrik.ipn.payment_status_Pending', '');
		$app = JFactory::getApplication();
		$MailFrom = $app->getCfg('mailfrom');
		$FromName = $app->getCfg('fromname');
		$SiteName = $app->getCfg('sitename');

		$payer_email = $request['payer_email'];
		$receiver_email = $request['receiver_email'];

		$subject = "%s - Payment Pending";
		$subject = sprintf($subject, $SiteName);
		$subject = html_entity_decode($subject, ENT_QUOTES);

		$msgbuyer = 'Your payment on %s is pending. (Paypal transaction ID: %s)<br /><br />%s';
		$txn_id = JRequest::getVar('txn_id', 'n/a');
		$msgbuyer = sprintf($msgbuyer, $SiteName, $txn_id, $SiteName);
		$msgbuyer = html_entity_decode($msgbuyer, ENT_QUOTES);
		JFactory::getMailer()->sendMail($MailFrom, $FromName, $payer_email, $subject, $msgbuyer, true);

		$msgseller = 'Payment pending on %s. (Paypal transaction ID: %s)<br /><br />%s';
		$msgseller = sprintf($msgseller, $SiteName, $txn_id, $SiteName);
		$msgseller = html_entity_decode($msgseller, ENT_QUOTES);
		JFactory::getMailer()->sendMail($MailFrom, $FromName, $receiver_email, $subject, $msgseller, true);
		return 'ok';
	}

	/**
	 * Reversed payment
	 *
	 * @param   object  $listModel  list model
	 * @param   array   $request    request data
	 * @param   array   &$set_list  new invoice properties
	 * @param   array   &$err_msg   error message
	 *
	 * @return	mixed false or 'ok'
	 */

	public function payment_status_Reversed($listModel, $request, &$set_list, &$err_msg)
	{
		$this->log('fabrik.ipn.payment_status_Reversed', '');
		return 'ok';
	}

	/**
	 * Cancelled reversed payment
	 *
	 * @param   object  $listModel  list model
	 * @param   array   $request    request data
	 * @param   array   &$set_list  new invoice properties
	 * @param   array   &$err_msg   error message
	 *
	 * @return	mixed false or 'ok'
	 */

	public function payment_status_Cancelled_Reversal($listModel, $request, &$set_list, &$err_msg)
	{
		$this->log('fabrik.ipn.payment_status_Cancelled_Reversal', '');
		return 'ok';
	}

	/**
	 * Refunded payment
	 *
	 * @param   object  $listModel  list model
	 * @param   array   $request    request data
	 * @param   array   &$set_list  new invoice properties
	 * @param   array   &$err_msg   error message
	 *
	 * @return	mixed false or 'ok'
	 */

	public function payment_status_Refunded($listModel, $request, &$set_list, &$err_msg)
	{
		$this->log('fabrik.ipn.payment_status_Refunded', '');
		$invoice = $this->checkInvoice($request);
		if (!$invoice)
		{
			return false;
		}
		$sub = $this->getSubscriptionFromInvoice($invoice);

		$now = JFactory::getDate()->toSql();
		$sub->status = 'Refunded';
		$sub->cancel_date = $now;
		$sub->eot_date = $now;
		$sub->store();
		return 'ok';
	}

	/**
	 * Transcation web accept
	 *
	 * @param   object  $listModel  list model
	 * @param   array   $request    request data
	 * @param   array   &$set_list  new invoice properties
	 * @param   array   &$err_msg   error message
	 *
	 * @return	mixed false or 'ok'
	 */

	public function txn_type_web_accept($listModel, $request, &$set_list, &$err_msg)
	{
		$this->log('fabrik.ipn.txn_type_web_accept', '');
		return 'ok';
	}

	/**
	 * Occurs when someone first signs up for a subscription,
	 * you should get a subscr_payment about 3 seconds afterwards.
	 * So again i dont think we need to do anything here
	 *
	 * @param   object  $listModel  list model
	 * @param   array   $request    request data
	 * @param   array   &$set_list  new invoice properties
	 * @param   array   &$err_msg   error message
	 *
	 * @return	mixed false or 'ok'
	 */

	public function txn_type_subscr_signup($listModel, $request, &$set_list, &$err_msg)
	{
		$this->log('fabrik.ipn.txn_type_subscr_signup', '');
		return 'ok';
	}

	/**
	 * The user has cancelled a subscription in Paypal,
	 *
	 * @param   object  $listModel  list model
	 * @param   array   $request    request data
	 * @param   array   &$set_list  new invoice properties
	 * @param   array   &$err_msg   error message
	 *
	 * @return	mixed false or 'ok'
	 */

	public function txn_type_subscr_cancel($listModel, $request, &$set_list, &$err_msg)
	{
		$this->log('fabrik.ipn.txn_type_subscr_cancel', '');
		$invoice = $this->checkInvoice($request);
		if (!$invoice)
		{
			$this->log('fabrik.ipn.txn_type_subscr_cancel invoice not found', $invoice . ' not found so didnt cancel a subscription');
			return false;
		}
		$sub = $this->getSubscriptionFromInvoice($invoice);
		if ($sub === false)
		{
			$this->log('fabrik.ipn.txn_type_subscr_cancel subscription not found', $invoice . ' not found so didnt cancel a subscription');
			return false;
		}
		$now = JFactory::getDate()->toSql();
		$sub->status = 'Cancelled';
		$sub->cancel_date = $now;
		$sub->store();
		$this->fallbackPlan($sub);
		return 'ok';
	}

	/**
	 * this occurs when a user upgrades their account in Paypal
	 * We don't allow this in fabrik so nothing should need to be done here
	 *
	 * @param   object  $listModel  list model
	 * @param   array   $request    request data
	 * @param   array   &$set_list  new invoice properties
	 * @param   array   &$err_msg   error message
	 *
	 * @return	mixed false or 'ok'
	 */

	public function txn_type_subscr_modify($listModel, $request, &$set_list, &$err_msg)
	{
		$this->log('fabrik.ipn.txn_type_subscr_modify', '');
		return 'ok';
	}

	/**
	 * A subscription payment has been successfully made
	 *
	 * @param   object  $listModel  list model
	 * @param   array   $request    request data
	 * @param   array   &$set_list  new invoice properties
	 * @param   array   &$err_msg   error message
	 *
	 * @return	mixed false or 'ok'
	 */

	public function txn_type_subscr_payment($listModel, $request, &$set_list, &$err_msg)
	{
		$db = JFactory::getDbo();
		$this->log('fabrik.ipn.txn_type_subscr_payment', json_encode($request));
		$invoice = $this->checkInvoice($request);
		if ($invoice === false)
		{
			return false;
		}
		// Update subscription details
		$inv = $this->getInvoice($invoice);

		$this->log('fabrik.ipn.txn_type_subscr_payment: invoice', json_encode($inv));

		$sub = $this->getSubscriptionFromInvoice($invoice);

		$this->log('fabrik.ipn.txn_type_subscr_payment: sub', json_encode($sub));

		$now = JFactory::getDate()->toSql();
		$sub->status = 'Active';
		$sub->lastpay_date = $now;
		$sub->store();

		// Update invoice status
		$inv->transaction_date = $now;
		$inv->pp_txn_id = $request['txn_id'];
		$inv->pp_payment_status = $request['payment_status'];
		$inv->pp_payment_amount = $request['mc_gross'];
		$inv->pp_txn_type = $request['txn_type'];
		$inv->pp_fee = $request['mc_fee'];
		$inv->pp_payer_email = $request['payer_email'];

		// $$$ hugh @TODO - make sure payment_amount == amount
		$inv->paid = 1;
		if (!$inv->store())
		{
			$this->log('fabrik.ipn.txn_type_subscr_payment: FAILED TO STORE INVOICE', json_encode($inv));
		}
		else
		{
			$this->log('fabrik.ipn.txn_type_subscr_payment: invoice stored', json_encode($inv));
		}

		// Set user to desired group
		$subUser = JFactory::getUser($sub->userid);
		$this->log('fabrik.ipn.txn_type_subscr_payment sub userid', $subUser->get('id'));

		$query = $db->getQuery(true);
		$query->select('usergroup AS gid')->from('#__fabrik_subs_subscriptions AS s')->join('INNER', '#__fabrik_subs_plans AS p ON p.id = s.plan')
			->where('userid = ' . $subUser->get('id'));
		$db->setQuery($query);
		$gid = $db->loadResult();

		$this->log('fabrik.ipn.txn_type_subscr_payment', 'set user group to:' . $gid);

		$this->log('fabrik.ipn.txn_type_subscr_payment gid query', $db->getQuery());

		$this->log('fabrik.ipn.setusergid', $subUser->get('id') . ' set to ' . $gid . "\n " . $db->getQuery() . "\n " . $db->getErrorMsg());
		$subUser->groups = (array) $gid;
		$subUser->save();

		$app = JFactory::getApplication();
		$MailFrom = $app->getCfg('mailfrom');
		$FromName = $app->getCfg('fromname');
		$SiteName = $app->getCfg('sitename');

		$payer_email = $request['payer_email'];

		$subject = "%s - Subscription payment complete";
		$subject = sprintf($subject, $SiteName);
		$subject = html_entity_decode($subject, ENT_QUOTES);

		$msgbuyer = 'Your subscription payment on %s has successfully completed. (Paypal transaction ID: %s)<br /><br />%s';
		$msgbuyer = sprintf($msgbuyer, $SiteName, $txn_id, $SiteName);
		$msgbuyer = html_entity_decode($msgbuyer, ENT_QUOTES);
		JFactory::getMailer()->sendMail($MailFrom, $FromName, $payer_email, $subject, $msgbuyer, true);

		$msgseller = 'Subscription payment success on %s. (Paypal transaction ID: %s)<br /><br />%s';
		$msgseller = sprintf($msgseller, $SiteName, $txn_id, $SiteName);
		$msgseller = html_entity_decode($msgseller, ENT_QUOTES);
		JFactory::getMailer()->sendMail($MailFrom, $FromName, $receiver_email, $subject, $msgseller, true);
		return 'ok';
	}

	/**
	 * this gets triggered when Paypal tries to charge the user for a recurring subscription
	 * but there are not enough funds in the account
	 * The user is emailed by Paypal regarding the issue
	 * If you have paypal's 'Re-attempt on Failure option' option turned on then Paypal will try to
	 * re-send the charge 3 days after the initial failed request.
	 * Once the max number of failures has occurred Paypal sends out a thx_type_subscr_cancel call
	 * Its there that we need to do something
	 *
	 * @param   object  $listModel  list model
	 * @param   array   $request    request data
	 * @param   array   &$set_list  new invoice properties
	 * @param   array   &$err_msg   error message
	 *
	 * @return	mixed false or 'ok'
	 */

	public function txn_type_subscr_failed($listModel, $request, &$set_list, &$err_msg)
	{
		$this->log('fabrik.ipn.txn_type_subscr_failed', '');
		return 'ok';
	}

	/**
	 * Seems to get called when you do a silver paypal payment (not sub)
	 * but as it occurs before anything else (eg. form.paypal.ipn.Completed the expired invoice doesnt
	 * rest expired but shows as active
	 *
	 * @param   object  $listModel  list model
	 * @param   array   $request    request data
	 * @param   array   &$set_list  new invoice properties
	 * @param   array   &$err_msg   error message
	 *
	 * @return	mixed false or 'ok'
	 */

	public function txn_type_subscr_eot($listModel, $request, &$set_list, &$err_msg)
	{
		$this->log('fabrik.ipn.txn_type_subscr_eot', '');
		$invoice = $this->checkInvoice($request);
		if (!$invoice)
		{
			return false;
		}
		$sub = $this->getSubscriptionFromInvoice($invoice);
		if ($sub->recurring != 1)
		{
			$this->log('fabrik.ipn.txn_type_subscr_eot', 'not expiring as sub is not recurring (so eot is triggered on sub signup)');
		}
		$now = JFactory::getDate()->toSql();
		$sub->status = 'Expired';
		$sub->eot_date = $now;
		$sub->store();
		return 'ok';
	}

	/**
	 * Get subscription row from a given invoice number
	 *
	 * @param   string  $inv  invoice number
	 *
	 * @return  JTable object
	 */

	private function getSubscriptionFromInvoice($inv)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('subscr_id')->from('#__fabrik_subs_invoices')->where('invoice_number = ' . $db->quote($inv));
		$db->setQuery($query);
		$subid = (int) $db->loadResult();
		if ($subid === 0)
		{
			return false;
		}
		$sub = JTable::getInstance('Subscription', 'FabrikTable');
		$sub->load($subid);
		return $sub;
	}

	/**
	 * Get an invoice JTable object from its invoice number
	 *
	 * @param   string  $inv  invoice number
	 *
	 * @return JTable
	 */

	private function getInvoice($inv)
	{
		$row = JTable::getInstance('Invoice', 'FabrikTable');
		$row->load(array('invoice_number' => $inv));
		return $row;
	}

	/**
	 *Report an error by email and to fabrik.subs.log.php
	 *
	 * @param   string  $msg   message
	 * @param   string  $to    to
	 * @param   array   $data  data to log
	 *
	 * @return unknown_type
	 */

	private function reportError($msg, $to, $data)
	{
		$app = JFactory::getApplication();
		$MailFrom = $app->getCfg('mailfrom');
		$FromName = $app->getCfg('fromname');
		$body = "\n\n\\";
		foreach ($data as $k => $v)
		{
			$body .= "$k = $v \n";
		}
		$subject = 'fabrik.ipn.fabrikar_subs error';
		JFactory::getMailer()->sendMail($MailFrom, $FromName, $to, $subject, $body);

		// Include the JLog class.
		jimport('joomla.log.log');

		// Add the logger.
		JLog::addLogger(array('text_file' => 'fabrik.subs.log.php'));

		// Start logging...
		JLog::add($body, JLog::ERROR, $subject);
		continue;
	}

	/**
	 * Log something
	 *
	 * @param   string  $subject  log subject
	 * @param   string  $body     log message
	 *
	 * @return  void
	 */

	private function log($subject, $body)
	{
		// Start logging...
		JLog::add($body, JLog::NOTICE, $subject);
	}

	/**
	 * Ensures that an invoice num was found in the request data.
	 *
	 * @param   array  $request  data to check
	 *
	 * @return  mixed	false if not found, otherwise returns invoice num
	 */

	private function checkInvoice($request)
	{
		$invoice = $request['invoice'];
		$receiver_email = $request['receiver_email'];

		// Eekk no invoice number found in returned data - inform the sys admin
		if ($invoice === '')
		{
			$this->reportError('missing invoice', $receiver_email, array_merge($request, $set_list));
			return false;
		}
		return $invoice;
	}

	/**
	 * If the plan has a fall back plan -
	 * e.g. original subscription was broze, updates to silver and silver expires - should fall back to bronze
	 * we want to change the user type
	 *
	 * @param   object  $sub  subscription
	 *
	 * @return  void
	 */

	public function fallbackPlan($sub)
	{
		// @TODO below code does not loook up the users actual plans but used a field fall_back_plan which we dont have now
		return;
		$plan = JTable::getInstance('Plan', 'FabrikTable');
		$newPlan = JTable::getInstance('Plan', 'FabrikTable');
		$plan->load((int) $sub->plan);
		$this->log('fabrik.ipn. fallback', ' getting fallback sub plan :  ' . (int) $sub->plan . ' = ' . (int) $plan->fall_back_plan);
		$fallback = false;
		if ($plan->fall_back_plan != 0)
		{
			$fallback = true;
			$newPlan->load((int) $plan->fall_back_plan);
			$gid = (int) $newPlan->usergroup;
		}
		else
		{
			$config = JComponentHelper::getParams('com_users');
			$gid = $config->get('new_usertype');
		}
		$subUser = JFactory::getUser($sub->userid);
		$this->log('fabrik.ipn. fallback', $subUser->get('id') . ' gid set to ' . $gid);
		$subUser->groups = (array) $gid;
		$subUser->save();

		if ($fallback)
		{
			// Create new subscription for fall back plan

			// Get the expration date (length of new plan - that of previous plan)
			$newLength = $this->charToPeriod($newPlan->period_unit);
			$oldLength = $this->charToPeriod($plan->period_unit);
			$expDate = strtotime("+{$newPlan->duration} $newLength	");

			$minus = strtotime("-{$plan->duration} $oldLength");

			$logMsg = 'expiration date = strtotime(+' . $newPlan->duration . ' ' . $newLength . ")\n minus = strtotime(-" . $plan->duration . ' '
						. $oldLength . ") \n =: $expDate - $minus";
			$this->log('fabrik.ipn. fallback', $logMsg);
			$expDate = JFactory::getDate()->toUnix() - ($expDate - $minus);

			$sub = JTable::getInstance('Subscription', 'FabrikTable');
			$sub->userid = $subUser->get('id');

			// Paypal payment - no recurring
			$sub->type = 1;
			$sub->status = 'Active';
			$sub->signup_date = JFactory::getDate()->toSql();
			$sub->plan = $newPlan->id;
			$sub->recurring = 0;
			$sub->lifetime = 0;
			$sub->expiration = JFactory::getDate($expDate)->toSql();
			$this->log('fabrik.ipn. fallback', 'new sub expiration set to ' . $sub->expiration);
			$sub->store();
			$msg = "<h3>new sub expiration set to $sub->expiration</h3>";
			foreach ($sub as $k => $v)
			{
				$msg .= "$k = $v <br>";
			}
			JFactory::getMailer()
				->sendMail('rob@pollen-8.co.uk', 'fabrikar subs', 'rob@pollen-8.co.uk', 'fabrikar sub: fall back plan created', $msg, true);
		}
	}

	/**
	 * Turn a time period character into its full name
	 *
	 *  @param   string  $p  period character
	 *
	 *  @return  string  name
	 */

	private function charToPeriod($p)
	{

		switch ($p)
		{
			case 'D':
				$newLength = 'day';
				break;
			case 'M':
				$newLength = 'month';
				break;
			case 'Y':
				$newLength = 'year';
				break;
			default:
				$newLength = '';
				break;
		}
		return $newLength;
	}

}
