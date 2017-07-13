<?php
/**
 * Copyright 2017 Lengow SAS
 *
 * NOTICE OF LICENSE
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * It is available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/agpl-3.0
 *
 * @category    Lengow
 * @package     Lengow
 * @subpackage  Controllers
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License, version 3
 */

/**
 * Backend Lengow Order Controller
 */
class Shopware_Controllers_Backend_LengowOrder extends Shopware_Controllers_Backend_Application implements \Shopware\Components\CSRFWhitelistAware
{
    protected $model = 'Shopware\CustomModels\Lengow\Order';
    protected $alias = 'order';

    public function getOrderDetailAction()
    {
        try {
            $orderId = $this->Request()->getParam('orderId');

            $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
            $repository = $em->getRepository('Shopware\CustomModels\Lengow\Order');
            $lengowOrder = $repository->findOneBy(array(
                'orderId' => 5
            ));
            if ($lengowOrder)
            var_dump($lengowOrder);die;

            $this->View()->assign(
                array(
                    'success' => true,
                    'data' => json_encode(Shopware()->Models()->toArray($lengowOrder))
                )
            );
        } catch (Exception $e) {
            $this->View()->assign(array(
                'success' => false,
                'error' => $e->getMessage()
            ));
        }
    }

    /**
     * Returns a list with actions which should not be validated for CSRF protection
     *
     * @return string[]
     */
    public function getWhitelistedCSRFActions()
    {
        return [
            'getOrderDetail',
        ];
    }
}