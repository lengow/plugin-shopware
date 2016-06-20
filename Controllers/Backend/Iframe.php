<?php

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\QueryExpressionVisitor;

class Shopware_Controllers_Backend_Iframe extends Shopware_Controllers_Backend_ExtJs
{
	const DEFAULT_IFRAME_ORIGIN_REGEXP = '(https:\/\/shopware-([a-z0-9]+)\.hub\.nosto\.com)|(https:\/\/my\.nosto\.com)';

	/**
	 * Loads the Nosto ExtJS sub-application for configuring Nosto for the shops.
	 * Default action.
	 */
	public function indexAction()
	{
		$this->View()->loadTemplate('backend/iframe/app.js');
	}

	/**
	 * Ajax action for getting any settings for the backend app.
	 *
	 * This action should only be accessed by the Main controller in the client
	 * side application.
	 */
	public function loadSettingsAction()
	{
		$this->View()->assign(
			array(
			'success' => true,
			'data' => array(
			'postMessageOrigin' => 
				self::DEFAULT_IFRAME_ORIGIN_REGEXP
			)
			)
		);
	}

	public function getAccountsAction()
	{
        $result['name']	= 'Register';
        $result['url']	= 'http://cms.lengow.int/sync/';

		$this->View()->assign(array('success' => true, 'data' => $result));

	}
}