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
 * @subpackage  Components
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License, version 3
 */

/**
 * Lengow Order Class
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowOrder
{
    /**
     * Get ID record from lengow orders table
     *
     * @param string  $marketplaceSku    Lengow order id
     * @param string  $marketplace       marketplace name
     * @param integer $deliveryAddressId Lengow delivery address id
     *
     * @return integer|false
     */
    public static function getIdFromLengowOrders($marketplaceSku, $marketplace, $deliveryAddressId)
    {
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        $repository = $em->getRepository('Shopware\CustomModels\Lengow\Order');
        $criteria = array(
            'marketplaceSku'    => $marketplaceSku,
            'marketplaceName'   => $marketplace,
            'deliveryAddressId' => $deliveryAddressId
        );
        // @var Shopware\CustomModels\Lengow\Order $lengowOrder
        $lengowOrder = $repository->findOneBy($criteria);
        if ($lengowOrder != null) {
            return $lengowOrder->getId();
        } else {
            return false;
        }
    }
}
