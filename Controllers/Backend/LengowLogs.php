<?php

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\QueryExpressionVisitor;

class Shopware_Controllers_Backend_LengowLogs extends Shopware_Controllers_Backend_ExtJs
{
	public function listAction()
	{
		$logFiles = Shopware_Plugins_Backend_Lengow_Components_LengowLog::getFiles();

		$result = array(
			array(	'path'	=> '',
					'name'	=> 'All'
				)
		);

		foreach ($logFiles as $logFile) {
			$result[] = array(
					'path'	=> $logFile->getPath(),
					'name'	=> $logFile->file_name
				);
		}

        $this->View()->assign(array(
            'success' => true,
            'data'    => $result,
            'total'   => count($result)
        ));
	}

}