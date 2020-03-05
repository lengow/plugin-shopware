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

use Shopware\CustomModels\Lengow\Order as LengowOrderModel;
use Shopware\CustomModels\Lengow\OrderError as LengowOrderErrorModel;

/**
 * Lengow Order Error Class
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowOrderError
{
    /**
     * @var integer order error import type
     */
    const TYPE_ERROR_IMPORT = 1;

    /**
     * @var integer order error send type
     */
    const TYPE_ERROR_SEND = 2;

    /**
     * Create an order error
     *
     * @param LengowOrderModel|integer $lengowOrder Lengow order instance
     * @param string $message error message
     * @param string $type error type (import or send)
     *
     * @return boolean
     */
    public static function createOrderError($lengowOrder, $message, $type = 'import')
    {
        try {
            $errorType = self::getOrderErrorType($type);
            if (is_integer($lengowOrder)) {
                $lengowOrder = Shopware()->Models()->getRepository('Shopware\CustomModels\Lengow\Order')
                    ->findOneBy(array('id' => $lengowOrder));
            }
            $orderError = new LengowOrderErrorModel();
            $orderError->setLengowOrder($lengowOrder)
                ->setMessage($message)
                ->setType($errorType)
                ->setCreatedAt(new DateTime());
            Shopware()->Models()->persist($orderError);
            Shopware()->Models()->flush($orderError);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Create an order error
     *
     * @param integer $orderErrorId Lengow order error id
     * @param array $params additional parameters for update
     *
     * @return boolean
     */
    public static function updateOrderError($orderErrorId, $params = array())
    {
        try {
            $orderError = Shopware()->Models()->getRepository('Shopware\CustomModels\Lengow\OrderError')
                ->findOneBy(array('id' => $orderErrorId));
            if (isset($params['is_finished'])) {
                $orderError->setIsFinished($params['is_finished']);
            }
            if (isset($params['mail'])) {
                $orderError->setMail($params['mail']);
            }
            $orderError->setUpdatedAt(new DateTime());
            Shopware()->Models()->flush($orderError);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Return type value
     *
     * @param string $type order error type (import or send)
     *
     * @return integer
     */
    public static function getOrderErrorType($type = null)
    {
        switch ($type) {
            case 'send':
                return self::TYPE_ERROR_SEND;
            case 'import':
            default:
                return self::TYPE_ERROR_IMPORT;
        }
    }

    /**
     * Removes all order error for one lengow order
     *
     * @param integer $lengowOrderId Lengow order id
     * @param string $type order error type (import or send)
     *
     * @return boolean
     */
    public static function finishOrderErrors($lengowOrderId, $type = 'import')
    {
        $type = self::getOrderErrorType($type);
        // get all order errors
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select('loe.id')
            ->from('Shopware\CustomModels\Lengow\OrderError', 'loe')
            ->where('loe.lengowOrderId = :lengowOrderId')
            ->andWhere('loe.isFinished = :isFinished')
            ->andWhere('loe.type = :type')
            ->setParameters(
                array(
                    'lengowOrderId' => $lengowOrderId,
                    'isFinished' => false,
                    'type' => $type,
                )
            );
        $results = $builder->getQuery()->getResult();
        if (!empty($results)) {
            foreach ($results as $result) {
                self::updateOrderError((int)$result['id'], array('is_finished' => true));
            }
            return true;
        }
        return false;
    }

    /**
     * Get order error not sent by email
     *
     * @return array|false
     */
    public static function getOrderErrorNotSent()
    {
        // get all order errors
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select('loe.id', 'loe.message', 'lo.marketplaceSku')
            ->from('Shopware\CustomModels\Lengow\OrderError', 'loe')
            ->leftJoin('loe.lengowOrder', 'lo')
            ->where('loe.isFinished = :isFinished')
            ->andWhere('loe.mail = :mail')
            ->setParameters(
                array(
                    'isFinished' => false,
                    'mail' => false,
                )
            );
        $results = $builder->getQuery()->getResult();
        if (!empty($results)) {
            return $results;
        }
        return false;
    }
}
