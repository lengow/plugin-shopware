<?php
/**
 * LengowImport.php
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @subpackage Lengow
 * @author     Lengow
 */

class Shopware_Controllers_Backend_LengowImport extends Shopware_Controllers_Backend_ExtJs
{

    public function getListAction()
    {
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select('orders')
                ->from('Shopware\CustomModels\Lengow\Order', 'orders');

        //If a filter is set
        if ($this->Request()->getParam('filter')) {
            //Get the value itself
            $filters = $this->Request()->getParam('filter');
            foreach ($filters as $filter) {
                $builder->andWhere($builder->expr()->orX(
                        'orders.idOrderLengow LIKE :value'))
                        ->setParameter('value', "%" . $filter["value"] . "%");
            }
        }

        $sort = $this->Request()->getParam('sort');
        if ($sort) {
            $sorting = $sort[0];
            switch ($sorting['property']) {
                case 'id':
                    $builder->orderBy('orders.id', $sorting['direction']);
                    break;
                case 'idOrderLengow':
                    $builder->orderBy('orders.idOrderLengow', $sorting['direction']);
                    break;
                case 'idFlux':
                    $builder->orderBy('orders.idFlux', $sorting['direction']);
                    break;
                case 'marketplace':
                    $builder->orderBy('orders.marketplace', $sorting['direction']);
                    break;
                case 'totalPaid':
                    $builder->orderBy('orders.totalPaid', $sorting['direction']);
                    break;
                case 'carrier':
                    $builder->orderBy('orders.carrier', $sorting['direction']);
                    break;
                case 'carrierMethod':
                    $builder->orderBy('orders.carrierMethod', $sorting['direction']);
                    break;
                case 'orderDate':
                    $builder->orderBy('orders.orderDate', $sorting['direction']);
                    break;
                case 'extra':
                    $builder->orderBy('orders.extra', $sorting['direction']);
                    break;
                default:
                    $builder->orderBy('orders.orderDate', 'DESC');
            }
        } else {
            $builder->orderBy('orders.orderDate', 'DESC');
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
     * Event listener function of the articles store to import orders from marketplaces
     *
     * @return mixed
     */
    public function importAction()
    {
        $test = 'Import orders';

        $this->View()->assign(array(
            'success' => true,
            'data'    => $test
        ));
    }

}