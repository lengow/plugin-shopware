<?php

/**
 * Copyright 2016 Lengow SAS.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 *
 * @author    Team Connector <team-connector@lengow.com>
 * @copyright 2016 Lengow SAS
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowOrder
{
    public static function getOrderIdFromLengowOrders($marketplace_sku, $marketplace, $delivery_address_id)
    {
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        $repository = $em->getRepository('Shopware\CustomModels\Lengow\Order');
        $criteria = array(
            'marketplaceSku'        => $marketplace_sku,
            'marketplaceName'       => $marketplace,
            'deliveryAddressId'     => $delivery_address_id
        );
        return $repository->findBy($criteria);
    }

    /**
     * Get ID record from lengow orders table
     *
     * @param string  $marketplaceSku       Lengow order id
     * @param integer $deliveryAddressId    Delivery address id
     *
     * @return mixed
     */
    public static function getIdFromLengowOrders($marketplaceSku, $deliveryAddressId)
    {
        $em = Shopware_Plugins_Backend_Lengow_Bootstrap::getEntityManager();
        $repository = $em->getRepository('Shopware\CustomModels\Lengow\Order');
        $criteria = array(
            'marketplaceSku'        => $marketplaceSku,
            'deliveryAddressId'     => $deliveryAddressId
        );
        /** @var Shopware\CustomModels\Lengow\Order $lengowOrder */
        $lengowOrder = $repository->findOneBy($criteria);
        if ($lengowOrder != null) {
            return $lengowOrder->getId();
        } else {
            return false;
        }
    }
}