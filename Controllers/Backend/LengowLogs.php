<?php

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\QueryExpressionVisitor;

class Shopware_Controllers_Backend_LengowLogs extends Shopware_Controllers_Backend_ExtJs
{
	/**
	 * Get list of log files
	 */
	public function listAction()
	{
		$files = Shopware_Plugins_Backend_Lengow_Components_LengowLog::getFiles();

		$result = array();

		foreach ($files as $logFile) {
			$name = $logFile->file_name;
			$date = substr($name, 5, 11);
            $dateTime = new DateTime($date);

			$result[] = array(
				'name' => $logFile->file_name,
				'date' => date_format($dateTime, 'd m Y')
				);
		}

        $this->View()->assign(array(
            'success' => true,
            'data'    => $result,
            'total'   => count($result)
        ));
	}

	/**
	 * Launch log file download
	 */
	public function downloadAction()
	{
		$fileName = $this->Request()->getParam('fileName');
		Shopware_Plugins_Backend_Lengow_Components_LengowLog::download($fileName);
	}
}