<?php

namespace Shopware\Lengow\Subscriber;

class ControllerPath
{
    /**
     * Register the backend controller
     *
     * @param   \Enlight_Event_EventArgs $args
     * @return  string
     * @Enlight\Event Enlight_Controller_Dispatcher_ControllerPath_Backend_Lengow     */
    public function onGetControllerPathBackend(\Enlight_Event_EventArgs $args)
    {
        return __DIR__ . '/../Controllers/Backend/Lengow.php';
    }

    public function lengowBackendControllerExport(\Enlight_Event_EventArgs $args)
    {
        return __DIR__ . '/../Controllers/Backend/LengowExport.php';
    }
}
