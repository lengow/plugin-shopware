<?php

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\QueryExpressionVisitor;

class Shopware_Controllers_Backend_Lengow extends Shopware_Controllers_Backend_ExtJs
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
            $builder->leftJoin('articles.categories', 'categories')
                ->leftJoin('articles.allCategories', 'allCategories')
                ->andWhere('categories.id = :categoryId')
                ->setParameter('categoryId', $categoryId);
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

    public function downloadLogAction($fileName = null)
    {
        Shopware_Plugins_Backend_Lengow_Components_LengowLog::download($fileName);
    }

    public function getAccountsAction()
    {
        /* @var \Shopware\Models\Shop\Repository $repository */
        $repository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop');
        if (method_exists($repository, 'getActiveShops')) {
            $result = $repository->getActiveShops(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
        } else {
            // SW 4.0 does not have the `getActiveShops` method, so we fall back
            // on manually building the query.
            $result = $repository->createQueryBuilder('shop')
                ->where('shop.active = 1')
                ->getQuery()
                ->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
        }
        $helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();
        $identity = Shopware()->Auth()->getIdentity();
        /*$setting = Shopware()
            ->Models()
            ->getRepository('\Shopware\CustomModels\Nosto\Setting\Setting')
            ->findOneBy(array('name' => 'oauthParams'));*/
        if (!is_null($setting)) {
            $oauthParams = json_decode($setting->getValue(), true);
            Shopware()->Models()->remove($setting);
            Shopware()->Models()->flush();
        }

        $data = array();
        $i = 0;
        foreach ($result as $row) {
            $i++;
            $params = array();
            $shop = $repository->getActiveById($row['id']);
            if (is_null($shop)) {
                continue;
            }
            $shop->registerResources(Shopware()->Bootstrap());
            /*$account = $helper->findAccount($shop);
            if (isset($oauthParams[$shop->getId()])) {
                $params = $oauthParams[$shop->getId()];
            }*/
            $accountData = array(
                'url' => '',//$helper->buildAccountIframeUrl($shop, $identity->locale, $account, $identity, $params),
                'shopId' => $i,
                'shopName' => "Shop" + $i,
            );
            if (!is_null($account)) {
                $accountData['id'] = $i++;
                $accountData['name'] = $account->getName();
            }
            $data[] = $accountData;
        }

        $this->View()->assign(array('success' => true, 'data' => $data, 'total' => count($data)));
    }

    public function loadSettingsAction()
    {
        $this->View()->assign(
            array(
                'success' => true,
                'data' => array(
                    'postMessageOrigin' => '(https:\/\/shopware-([a-z0-9]+)\.hub\.nosto\.com)|(https:\/\/my\.nosto\.com)'
                )
            )
        );
    }
}