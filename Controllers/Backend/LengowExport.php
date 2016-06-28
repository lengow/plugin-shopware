<?php

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\QueryExpressionVisitor;

class Shopware_Controllers_Backend_LengowExport extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * Event listener function of articles store to list Lengow products
     *
     * @return mixed
     */
    public function getlistAction()
    {
        $categoryId   = $this->Request()->getParam('categoryId');
        $filterParams = $this->Request()->getParam('filter', array());
        $filterBy     = $this->Request()->getParam('filterBy');
        $order        = $this->Request()->getParam('sort', null);
        $start        = $this->Request()->getParam('start', 0);
        $limit        = $this->Request()->getParam('limit', 20);

        // TODO : replace with selected shop
        $export_out_stock = (bool)Shopware_Plugins_Backend_Lengow_Components_LengowCore::getConfigValue('lengowExportOutOfStock');
        $export_disabled_products = (bool)Shopware_Plugins_Backend_Lengow_Components_LengowCore::getConfigValue('lengowExportDisabledProduct');

        $filters = array();
        foreach ($filterParams as $singleFilter) {
            $filters[$singleFilter['property']] = $singleFilter['value'];
        }

        $select = array(
            'attributes.id AS attributeId',
            'articles.name AS name',
            'suppliers.name AS supplier',
            'articles.active AS status',
            'details.number AS number',
            'details.inStock',
            'tax.tax AS vat',
            'prices.price*(100+tax.tax)/100 AS price',
            'attributes.lengowLengowActive AS activeLengow'
        );

        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select($select)
            ->from('Shopware\Models\Article\Article', 'articles')
            ->leftJoin('articles.mainDetail', 'details')
            ->leftJoin('articles.supplier', 'suppliers')
            ->leftJoin('articles.attribute', 'attributes')
            ->leftJoin('details.prices', 'prices')
            ->leftJoin('articles.tax', 'tax')
            ->where('prices.to = \'beliebig\'')
            ->andWhere('prices.customerGroupKey = \'EK\'')
            ->andWhere('attributes.articleDetailId = details.id');

        // Search criteria
        if (isset($filters['search'])) {
            $searchFilter = '%' . $filters['search'] . '%';
            $condition = 'details.number LIKE :searchFilter OR ' .
                'articles.name LIKE :searchFilter OR ' .
                'suppliers.name LIKE :searchFilter';
            $builder->andWhere($condition)
                ->setParameter('searchFilter', $searchFilter);
        }

        // In stock products only
        if ($export_out_stock) {
            $builder->andWhere('details.inStock > 0');
        }

        // Active product only
        if ($export_disabled_products) {
            $builder->andWhere('articles.active = 1');
        }

        if (!empty($categoryId) && $categoryId !== 'NaN') {
            $category = Shopware()->Models()->getReference(
                    'Shopware\Models\Category\Category',
                    $categoryId
            );

            // Construct where clause with selected category children 
            $where = $this->getAllCategoriesClause($category);

            $builder->leftJoin('articles.categories', 'categories')
                ->innerJoin('articles.allCategories', 'allCategories')
                ->andWhere($where);
        }

        // Make sure that whe don't get a cold here
        $columns = array(
            'number',
            'name',
            'supplier',
            'status',
            'price',
            'tax',
            'inStock',
            'activeLengow'
        );

        $directions = array('ASC', 'DESC');
        if (null === $order ||
            !in_array($order[0]['property'] , $columns) ||
            !in_array($order[0]['direction'], $directions)
        ) {
            $builder->orderBy('articles.id');
        } else {
            $order = array_shift($order);

            switch($order['property']) {
                case 'active':
                    $orderColumn = 'details.active';
                    break;
                case 'inStock':
                    $orderColumn = 'details.inStock';
                    break;
                case 'status':
                    $orderColumn = 'articles.active';
                    break;
                default:
                    $orderColumn = $order['property'];
                    break;
            }

            $builder->orderBy($orderColumn, $order['direction']);
        }

        $builder->distinct();

        // Get number of products before setting offset and limit parameters
        $articles = $builder->getQuery()->getArrayResult();
        $numberOfProducts = count($articles);

        $nbLengowProducts = 0;
        foreach ($articles as $article) {
            if ($article['activeLengow'] == 1) {
                $nbLengowProducts++;
            }
        }

        $builder->addOrderBy('details.number')
            ->setFirstResult($start)
            ->setMaxResults($limit);

        $result = $builder->getQuery()->getArrayResult();

        $this->View()->assign(array(
            'success' => true,
            'data'    => $result,
            'total'   => $numberOfProducts,
            'nbLengowProducts'   => $nbLengowProducts
        ));
    }

    /**
     * Generate where clause used to list articles from a selected category
     * @param $selectedCategory Shopware\Models\Category\Category List of children of the selected category
     * @return string Exclusive clause which contains all sub-categories ids
     */
    private function getAllCategoriesClause($selectedCategory)
    {
        $children = $selectedCategory->getChildren();
        $where = 'categories.id = ' . $selectedCategory->getId();

        foreach ($children as $child) {
            if ($child->isLeaf()) {
                $where.= ' OR categories.id = ' . $child->getId();
            } else {
                $where.= ' OR ' . $this->getAllCategoriesClause($child);
            }
        }

        return $where;
    }

    /**
     * Event listener function of articles store to export a list of products
     * 
     * @return mixed
     */
    public function exportAction()
    {   
        $host = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getBaseUrl(); 
        $pathPlugin = Shopware_Plugins_Backend_Lengow_Components_LengowCore::getPathPlugin();
        $exportUrl = $host . $pathPlugin . 'Webservice/export.php';

        $this->View()->assign(array(
            'success' => true,
            'url' => $exportUrl
        ));
    }

    /**
     * Set Lengow status for an article
     *
     */
    public function setStatusInLengowAction()
    {
        $ids = $this->Request()->getParam('ids');
        $status = $this->Request()->getParam('status', false);
        $active = $status == 'true' ? true : false;

        $attributeIds = json_decode($ids);

        foreach ($attributeIds as $id) {
            $em = Shopware()->Models();
            $attribute = $em->getReference('Shopware\Models\Attribute\Article', $id);

            if ($attribute) {
                $attribute->setLengowLengowActive($active);
                $em->persist($attribute);
                $em->flush($attribute);
            }
        }
    }
}