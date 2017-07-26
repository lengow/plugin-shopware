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
 * Lengow Action Class
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowAction
{
    /**
     * @var integer action state for new action
     */
    const STATE_NEW = 0;

    /**
     * @var integer action state for action finished
     */
    const STATE_FINISH = 1;

    /**
     * Create an order action
     *
     * @param \Shopware\Models\Order\Order $order Shopware order instance
     * @param string $actionType action type (ship or cancel)
     * @param integer $actionId Lengow action id
     * @param string $orderLineId Lengow order line id
     * @param array $params order action parameters
     *
     * @return boolean
     */
    public static function createOrderAction($order, $actionType, $actionId, $orderLineId = null, $params = array())
    {
        try {
            $orderAction = new Shopware\CustomModels\Lengow\Action();
            $orderAction->setOrder($order)
                ->setActionType($actionType)
                ->setActionId($actionId)
                ->setOrderLineSku($orderLineId)
                ->setParameters(json_encode($params))
                ->setState(self::STATE_NEW)
                ->setCreatedAt(new DateTime())
                ->setUpdatedDate(new DateTime());
            Shopware()->Models()->persist($orderAction);
            Shopware()->Models()->flush($orderAction);
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                'API-OrderAction',
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage('log/order_action/action_saved'),
                false,
                $params['marketplace_order_id']
            );
        } catch (Exception $e) {
            $errorMessage = '[Doctrine error] "' . $e->getMessage() . '" ' . $e->getFile() . ' | ' . $e->getLine();
            Shopware_Plugins_Backend_Lengow_Components_LengowMain::log(
                'Orm',
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                    'log/exception/order_insert_failed',
                    array('decoded_message' => $errorMessage)
                )
            );
            return false;
        }
        return true;
    }
}
