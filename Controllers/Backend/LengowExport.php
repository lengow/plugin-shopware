<?php
/**
 * LengowExport.php
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @subpackage Lengow
 * @author     Lengow
 */

class Shopware_Controllers_Backend_LengowExport extends Shopware_Controllers_Backend_ExtJs
{

	public function getListAction()
    {
        $categoryId   = $this->Request()->getParam('categoryId');
        $filterParams = $this->Request()->getParam('filter', array());
        $filterBy     = $this->Request()->getParam('filterBy');
        $order        = $this->Request()->getParam('sort', null);
        $start        = $this->Request()->getParam('start', 0);
        $limit        = $this->Request()->getParam('limit', 20);

        $filters = array();
        foreach ($filterParams as $singleFilter) {
            $filters[$singleFilter['property']] = $singleFilter['value'];
        }

        $categorySql = '';
        $sqlParams = array();

        $filterSql = 'WHERE 1 = 1';
        if (isset($filters['search'])) {
            $filterSql .= " AND (details.ordernumber LIKE :orderNumber 
                OR articles.name LIKE :articleName 
                OR suppliers.name LIKE :supplierName)";
            $searchFilter =  '%' . $filters['search'] . '%';
            $sqlParams["orderNumber"] = $searchFilter;
            $sqlParams["articleName"] = $searchFilter;
            $sqlParams["supplierName"] = $searchFilter;
        }

        if ($filterBy == 'inStock') {
            $filterSql .= " AND details.instock > 0 ";
        }
        if ($filterBy == 'lengowProduct') {
            $filterSql .= " AND attributes.lengow_lengowActive = 1 ";
        }
        if ($filterBy == 'activeProduct') {
            $filterSql .= " AND articles.active = 1 ";
        }
        if ($filterBy == 'noCategory') {
            $categorySql = "
                    LEFT JOIN s_articles_categories_ro ac
                    ON ac.articleID = articles.id
            ";
            $filterSql .= " AND ac.id IS NULL ";
        } elseif (!empty($categoryId) && $categoryId !== 'NaN') {
            $categorySql =  "
                LEFT JOIN s_categories c
                    ON  c.id = :categoryId
                INNER JOIN s_articles_categories_ro ac
                    ON  ac.articleID  = articles.id
                    AND ac.categoryID = c.id
            ";
            $sqlParams["categoryId"] = $categoryId;
        }

        // Make sure that whe don't get a cold here
        $columns = array('number', 'name', 'supplier', 'active', 'inStock', 'price', 'tax', 'activeLengow' );
        $directions = array('ASC', 'DESC');
        if (null === $order || !in_array($order[0]['property'] , $columns) || !in_array($order[0]['direction'], $directions)) {
            $order = 'id DESC';
        } else {
            $order = array_shift($order);
            $order = $order['property'] . ' ' . $order['direction'];
        }
       
        $sql = "
            SELECT DISTINCT SQL_CALC_FOUND_ROWS
                   details.id as id,
                   articles.id as articleId,
                   articles.name as name,
                   suppliers.name as supplier,
                   articles.active as active,
                   details.id as detailId,
                   details.instock as inStock,
                   details.ordernumber as number,
                   ROUND(prices.price*(100+tax.tax)/100,2) as `price`,
                   tax.tax as tax,
                   attributes.lengow_lengowActive as activeLengow
            FROM s_articles as articles
            INNER JOIN s_articles_details as details
                ON articles.main_detail_id = details.id
            LEFT JOIN s_articles_supplier as suppliers
                ON articles.supplierID = suppliers.id
            LEFT JOIN s_articles_attributes as attributes
                ON attributes.articleID = articles.id
            LEFT JOIN s_articles_prices prices
                ON prices.articledetailsID = details.id
                AND prices.`to`= 'beliebig'
                AND prices.pricegroup='EK'
            LEFT JOIN s_core_tax AS tax
                ON tax.id = articles.taxID
            $categorySql
            $filterSql
            ORDER BY $order, details.ordernumber ASC
            LIMIT  $start, $limit
        ";

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
     * Event listener function of the articles store of the backend module
     *
     * @return mixed
     */
    public function updateAction()
    {
        $articleId = (int) $this->Request()->getParam('articleId');

        $active = $this->Request()->getPost('activeLengow');

        Shopware()->Db()->update(
            's_articles_attributes',
            array('lengow_lengowActive' => $active),
            array('articleId = ?' => $articleId)
        );

        Shopware()->Models()->flush();

        $this->View()->assign(array(
            'success' => true,
            'data'    => $this->Request()->getPost()
        ));
    }

    /**
     * Event listener function of the articles store to export a list of products
     *
     * @return mixed
     */
    public function exportAction()
    {
        
        $product = new Shopware_Plugins_Backend_Lengow_Components_LengowProduct(2, 124);
        $id = $product->getData('id_article');
        $result = $product->getExportIds(false, true); 
        $last = end($result);
        $test = count($result);
        
        $this->View()->assign(array(
            'success' => true,
            'id' => $id,
            'data'    => $test,
            'result' => $result,
            'last' => $last
        ));
    }


}