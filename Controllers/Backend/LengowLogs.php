<?php

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\QueryExpressionVisitor;

class Shopware_Controllers_Backend_LengowLogs extends Shopware_Controllers_Backend_ExtJs
{
	public function listAction()
	{
		$result = array(
			array(
				'id'	=> 1,
				'allowDrag'	=> false,
				'name'	=> 'oto',
				'active'=> true,
				'childrenCount'	=> 0,
				'cls'	=> 'folder',
				'leaf'	=> false,
				'parentId'	=> 1,
				'position'	=> 0,
				'text'		=> 'test'
			),
			array(
				'id'	=> 2,
				'allowDrag'	=> false,
				'name'	=> 'yrteyryry',
				'active'=> true,
				'childrenCount'	=> 0,
				'cls'	=> 'folder',
				'leaf'	=> false,
				'parentId'	=> 1,
				'position'	=> 0,
				'text'		=> 'yretyreyry'
			)
		);

        $this->View()->assign(array(
            'success' => true,
            'data'    => $result,
            'total'   => 1
        ));
	}

}