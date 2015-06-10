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

    /**
     * Event listener function of orders store to list Lengow order
     *
     * @return mixed
     */
    public function getListAction()
    {
        $filterParams = $this->Request()->getParam('filter', array());
        $filterBy     = $this->Request()->getParam('filterBy');
        $order        = $this->Request()->getParam('sort', null);
        $start        = $this->Request()->getParam('start', 0);
        $limit        = $this->Request()->getParam('limit', 20);

        $filters = array();
        foreach ($filterParams as $singleFilter) {
            $filters[$singleFilter['property']] = $singleFilter['value'];
        }

        $sqlParams = array();

        $filterSql = 'WHERE 1 = 1';
        if (isset($filters['search'])) {
            $filterSql .= " AND (lo.idOrderLengow LIKE :idOrderLengow 
                OR lo.idFlux LIKE :idFlux
                OR so.ordernumber LIKE :orderNumber
                OR sob.company LIKE :company
                OR sob.firstname LIKE :firstname
                OR sob.lastname LIKE :lastname)";
            $searchFilter =  '%' . $filters['search'] . '%';
            $sqlParams["idOrderLengow"] = $searchFilter;
            $sqlParams["idFlux"] = $searchFilter;
            $sqlParams["orderNumber"] = $searchFilter;
            $sqlParams["company"] = $searchFilter;
            $sqlParams["firstname"] = $searchFilter;
            $sqlParams["lastname"] = $searchFilter;
        }

        // Make sure that whe don't get a cold here
        $columns = array(
            'id', 
            'idOrderLengow', 
            'idFlux', 
            'marketplace', 
            'totalPaid', 
            'carrier', 
            'trackingNumber', 
            'orderDateLengow',
            'cost',  
            'extra', 
            'orderId', 
            'orderDate', 
            'orderNumber',
            'invoiceAmount', 
            'nameShop', 
            'shipping',
            'status', 
            'nameCustomer'
        );

        $directions = array('ASC', 'DESC');
        if (null === $order || !in_array($order[0]['property'] , $columns) || !in_array($order[0]['direction'], $directions)) {
            $order = 'orderDate DESC';
        } else {
            $order = array_shift($order);
            $order = $order['property'] . ' ' . $order['direction'];
        }
       
        $sql = "SELECT DISTINCT SQL_CALC_FOUND_ROWS
                    lo.id as id,
                    lo.idOrderLengow as idOrderLengow,
                    lo.idFlux as idFlux,
                    lo.marketplace as marketplace,
                    lo.totalPaid as totalPaid,
                    lo.carrier as carrier,
                    lo.trackingNumber as trackingNumber,
                    lo.orderDate as orderDateLengow,
                    lo.cost as cost,
                    lo.extra as extra,
                    so.id as orderId,
                    so.ordertime as orderDate,
                    so.ordernumber as orderNumber,
                    so.invoice_amount as invoiceAmount,
                    scs.name as nameShop,
                    spd.name as shipping,
                    scst.description as status,
                    CASE 
                        WHEN LENGTH(sob.company) > 0 THEN sob.company 
                        ELSE CONCAT(sob.firstname, ' ', sob.lastname)
                    END as nameCustomer             
                FROM lengow_orders as lo
                INNER JOIN s_order as so
                    ON so.id = lo.orderID
                LEFT JOIN s_core_shops as scs
                    ON scs.id = so.subshopID
                LEFT JOIN s_premium_dispatch as spd
                    ON spd.id = so.dispatchID
                LEFT JOIN s_core_states as scst
                    ON scst.id = so.status
                LEFT JOIN s_order_billingaddress as sob
                    ON sob.orderID = so.id
                $filterSql
                ORDER BY $order
                LIMIT  $start, $limit";

        $articles = Shopware()->Db()->fetchAll($sql, $sqlParams);

        $sql= "SELECT FOUND_ROWS() as count";
        $count = Shopware()->Db()->fetchOne($sql);

        $this->View()->assign(array(
            'success' => true,
            'data'    => $articles,
            'total'   => $count
        ));
    }

    /**
     * Event listener function of the orders store to import orders from marketplaces
     *
     * @return mixed
     */
    public function importAction()
    {
        $host = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getBaseUrl();
        $pathPlugin = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getPathPlugin();
        $exportUrl = $host . $pathPlugin . 'Webservice/import.php';

        $this->View()->assign(array(
            'success' => true,
            'url' => $exportUrl
        ));
    }

}