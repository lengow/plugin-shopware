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
     * Internal helper function which selects an filtered and sorted offset of log.
     * @param $filter
     * @param $sort
     * @param $offset
     * @param $limit
     *
     * @return array
     */
    protected function getList($filter, $sort, $offset, $limit) {

        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select(array('logs'))
                ->from('Shopware\CustomModels\Lengow\Log', 'logs');
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