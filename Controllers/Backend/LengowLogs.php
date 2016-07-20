<?php

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\QueryExpressionVisitor;

/**
 * Copyright 2016 Lengow SAS.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 *
 * @author    Team Connector <team-connector@lengow.com>
 * @copyright 2016 Lengow SAS
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 */
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
