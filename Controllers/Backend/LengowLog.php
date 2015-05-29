<?php
/**
 * LengowLog.php
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @subpackage Lengow
 * @author     Lengow
 */

class Shopware_Controllers_Backend_LengowLog extends Shopware_Controllers_Backend_ExtJs
{

    /**
     * Event listener function of logs store to list Lengow logs
     *
     * @return mixed
     */
    public function getListAction()
    {
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select('logs')
                ->from('Shopware\CustomModels\Lengow\Log', 'logs');

        //If a filter is set
        if ($this->Request()->getParam('filter')) {
            //Get the value itself
            $filters = $this->Request()->getParam('filter');
            foreach ($filters as $filter) {
                $builder->andWhere($builder->expr()->orX('logs.message LIKE :value'))
                        ->setParameter('value', "%" . $filter["value"] . "%");
            }
        }

        $sort = $this->Request()->getParam('sort');
        if ($sort) {
            $sorting = $sort[0];
            switch ($sorting['property']) {
                case 'id':
                    $builder->orderBy('logs.id', $sorting['direction']);
                    break;
                case 'created':
                    $builder->orderBy('logs.created', $sorting['direction']);
                    break;
                case 'message':
                    $builder->orderBy('logs.message', $sorting['direction']);
                    break;
                default:
                    $builder->orderBy('logs.created', 'DESC');
            }
        } else {
            $builder->orderBy('logs.created', 'DESC');
        }

        $builder->setFirstResult($this->Request()->getParam('start'))
                ->setMaxResults($this->Request()->getParam('limit'));

        $query = $builder->getQuery();

        $count = Shopware()->Models()->getQueryCount($builder->getQuery());
        $data = $query->getArrayResult();

        $this->View()->assign(array(
            'success' => true,
            'data' => $data,
            'total' => $count
        ));
    }

    
    /**
     * Event listener function of logs store to delete Lengow logs
     *
     * @return mixed
     */
    public function deleteAction() 
    {
        $logId = (int) $this->Request()->getParam('id');

        if (empty($logId)) {
            $this->View()->assign(array(
                'success' => false, 
                'error' => 'No id passed'
            ));
        }

        $log = Shopware()->Models()->getReference('\Shopware\CustomModels\Lengow\Log',(int) $logId);

        if (!($log instanceof \Shopware\CustomModels\Lengow\Log)) {
            $this->View()->assign(array(
                'success' => false,
                'error'   => "The passed id '" . $logId . " doesn't exist"
            ));
        }

        try {
            Shopware()->Models()->remove($log);
            Shopware()->Models()->flush();
            $this->View()->assign(array(
                'success' => true
            ));
        } catch (Exception $e) {
            $this->View()->assign(array(
                'success' => true,
                'error'   => $e->getMessage()
            ));
        }
    }

    /**
     * Event listener function of logs store to delete all Lengow logs
     *
     * @return mixed
     */
    public function flushLogsAction()
    {
        try {
            Shopware()->Db()->exec("TRUNCATE lengow_logs");
            $this->View()->assign(array(
                'success' => true
            ));
        } catch (Exception $e) {
            $this->View()->assign(array(
                'success' => true,
                'error'   => $e->getMessage()
            ));
        }
    }

}