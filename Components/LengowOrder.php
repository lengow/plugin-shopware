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
     * Get Shopware order id from lengow order table
     *
     * @param string $marketplaceSku Lengow order id
     * @param string $marketplaceName marketplace name
     * @param integer $deliveryAddressId Lengow delivery address id
     *
     * @return integer|false
     */
    public static function getOrderIdFromLengowOrder($marketplaceSku, $marketplaceName, $deliveryAddressId)
    {
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        $builder = $em->createQueryBuilder();
        $builder->select('lo.orderId')
            ->from('Shopware\CustomModels\Lengow\Order', 'lo')
            ->where('lo.marketplaceSku = :marketplaceSku')
            ->andWhere('lo.marketplaceName = :marketplaceName')
            ->andWhere('lo.deliveryAddressId = :deliveryAddressId')
            ->andWhere('lo.orderProcessState != :orderProcessState')
            ->setParameters(
                array(
                    'marketplaceSku' => $marketplaceSku,
                    'marketplaceName' => $marketplaceName,
                    'deliveryAddressId' => $deliveryAddressId,
                    'orderProcessState' => 0
                )
            );
        $orderId = $builder->getQuery()->getOneOrNullResult();
        if (!is_null($orderId['orderId'])) {
            return (int)$orderId['orderId'];
        }
        return false;
    }
}
