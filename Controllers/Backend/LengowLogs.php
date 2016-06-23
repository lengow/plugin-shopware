<?php

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\QueryExpressionVisitor;

class Shopware_Controllers_Backend_LengowLogs extends Shopware_Controllers_Backend_ExtJs
{
	private $downloadAllLabel = 'All';
	public function listAction()
	{
		$files = Shopware_Plugins_Backend_Lengow_Components_LengowLog::getFiles();

		$result = array(
			array(	'id'	=> '',
					'name'	=> $this->downloadAllLabel
				)
		);

		foreach ($files as $logFile) {
			$result[] = array('name' => $logFile->file_name);
		}

        $this->View()->assign(array(
            'success' => true,
            'data'    => $result,
            'total'   => count($result)
        ));
	}

	public function downloadAction()
	{
		$fileName = $this->Request()->getParam('fileName');
		Shopware_Plugins_Backend_Lengow_Components_LengowLog::download($fileName);
	}
}