<?php

class Shopware_Controllers_Frontend_Cleverpush extends \Enlight_Controller_Action
{
    public function setSubscriptionAction()
    {
        Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();

        $subscriptionId = $this->Request()->getPost('subscriptionId');

        if (empty($subscriptionId)) {
            die(json_encode(array('error' => 'empty subscriptionId!')));
        }

        // set session
        Shopware()->Session()->cleverPushSubscriptionId = $subscriptionId;

        die(json_encode(array('success' => true, 'subscriptionId' => $subscriptionId)));
    }
}
