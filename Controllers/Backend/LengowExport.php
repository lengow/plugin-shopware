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
    public function listAction()
    {
        $categoryId   = $this->Request()->getParam('categoryId');
        $filterParams = $this->Request()->getParam('filter', array());
        $filterBy     = $this->Request()->getParam('filterBy');
        $order        = $this->Request()->getParam('sort', null);
        $start        = $this->Request()->getParam('start', 0);
        $limit        = $this->Request()->getParam('limit', 20);
        $variant      = $this->Request()->getParam('variant') == 'true' ? true : false;

        $filters = array();
        foreach ($filterParams as $singleFilter) {
            $filters[$singleFilter['property']] = $singleFilter['value'];
        }

        $select = array(
            'articles.id AS articleId',
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
            ->from('Shopware\Models\Article\Article', 'articles');

        // If variation option has been selected
        if ($variant) {
            $builder->innerJoin('articles.details', 'details');
        } else {
            $builder->innerJoin('articles.mainDetail', 'details');
        }

        $builder->leftJoin('articles.supplier', 'suppliers')
            ->leftJoin('articles.attribute', 'attributes')
            ->leftJoin('details.prices', 'prices')
            ->leftJoin('articles.tax', 'tax')
            ->where('prices.to = \'beliebig\'')
            ->andWhere('prices.customerGroupKey = \'EK\'');

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
        if ($filterBy == 'inStock') {
            $builder->andWhere('details.inStock > 0');
        }

        // Lengow selection
        if ($filterBy == 'lengowProduct') {
            $builder->andWhere('attributes.lengowLengowActive = 1');
        }

        // Active product only
        if ($filterBy == 'activeProduct') {
            $builder->andWhere('articles.active = 1');
        }

        if ($filterBy == 'noCategory') {
            $builder->leftJoin('articles.allCategories', 'allCategories')
                ->andWhere('allCategories.id IS NULL');
        } elseif (!empty($categoryId) && $categoryId !== 'NaN') {
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
        $numberOfProducts = count($builder->getQuery()->getScalarResult());

        $builder->addOrderBy('details.number')
            ->setFirstResult($start)
            ->setMaxResults($limit);

        $result = $builder->getQuery()->getArrayResult();

        $this->View()->assign(array(
            'success' => true,
            'data'    => $result,
            'total'   => $numberOfProducts
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

    public function downloadLogAction($fileName = null)
    {
        Shopware_Plugins_Backend_Lengow_Components_LengowLog::download($fileName);
    }
}