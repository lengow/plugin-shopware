<?php

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\QueryExpressionVisitor;

class Shopware_Controllers_Backend_Iframe extends Shopware_Controllers_Backend_ExtJs
{
	public function indexAction()
	{
		$this->View()->loadTemplate('backend/iframe/app.js');
	}

	public function getUrlAction()
	{
        $result['name']	= 'Register';
        $result['url']	= 'http://cms.lengow.int/sync/';

		$this->View()->assign(array('success' => true, 'data' => $result));

	}
}