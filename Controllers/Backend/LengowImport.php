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

	public function getListAction() {
		$this->View()->assign(
            $this->getList(
                $this->Request()->getParam('filter'),
                $this->Request()->getParam('sort'),
                $this->Request()->getParam('offset'),
                $this->Request()->getParam('limit')
            )
        );
	}

	/**
     * Internal helper function which selects an filtered and sorted offset of order.
     * @param $filter
     * @param $sort
     * @param $offset
     * @param $limit
     *
     * @return array
     */
    protected function getList($filter, $sort, $offset, $limit) {

        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select(array('orders'))
                ->from('Shopware\CustomModels\Lengow\Order', 'orders');
        if (!empty($filter)) {
            $builder->addFilter($filter);
        }
        if (!empty($sort)) {
            $builder->addOrderBy($sort);
        }
        $builder->setFirstResult($offset)
                ->setMaxResults($limit);

        $query = $builder->getQuery();
        $query->setHydrationMode(
            \Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY
        );

        $paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($query);

		return array(
            'success' => true,
            'total'   => $paginator->count(),
            'data'    => $paginator->getIterator()->getArrayCopy()
        ); 
    }
}