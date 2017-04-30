<?php

namespace CleverPush;

use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Common\Collections\ArrayCollection;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;

class CleverPush extends Plugin
{
    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Frontend' => 'onPostDispatch',
            'sBasket::sAddArticle::after' => 'onBasketAddArticle',
            'Shopware_CronJob_CleverPushCheckBasket' => 'checkBasketCron',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_Cleverpush' => 'registerController',
            'Theme_Compiler_Collect_Plugin_Javascript' => 'addJsFiles'
        ];
    }

    public function registerController(\Enlight_Event_EventArgs $args)
    {
        return $this->getPath() . '/Controllers/Frontend/Cleverpush.php';
    }

    public function onBasketAddArticle(\Enlight_Hook_HookArgs $args)
    {
        $basketId = $args->getReturn();

        $subscriptionId = Shopware()->Session()->cleverPushSubscriptionId;
        if (!empty($subscriptionId))
        {
            // insert cron
            $notificationMinutes = $this->container->get('shopware.plugin.config_reader')->getByPluginName($this->getName())->notificationMinutes;
            if (empty($notificationMinutes)) {
                $notificationMinutes = 30;
            } else {
                $notificationMinutes = intval($notificationMinutes);
            }

            $cronTime = new \DateTime();
            $cronTime->modify('+' . $notificationMinutes . ' minutes');
            $connection = $this->container->get('dbal_connection');
            $connection->insert(
                's_crontab',
                [
                    'name'             => 'CleverPushCheckBasket',
                    'action'           => 'Shopware_CronJob_CleverPushCheckBasket',
                    'next'             => $cronTime,
                    'start'            => null,
                    '`interval`'       => '0',
                    'active'           => 1,
                    'end'              => $cronTime,
                    'pluginID'         => $this->container->get('shopware.plugin_manager')->getPluginByName('CleverPush')->getId(),
                    'data'             => serialize(array('subscriptionId' => $subscriptionId, 'basketId' => $basketId))
                ],
                [
                    'next' => 'datetime',
                    'end'  => 'datetime',
                ]
            );
        }
    }

    public function onPostDispatch(\Enlight_Event_EventArgs $args)
    {
        $controller = $args->get('subject');

        $view = $controller->View();
        $view->addTemplateDir(
            $this->getPath() . '/Views'
        );

        $view->assign('cleverPushConfig', json_encode($this->container->get('shopware.plugin.config_reader')->getByPluginName($this->getName())));
    }

    public function checkBasketCron(\Shopware_Components_Cron_CronJob $jobArgs)
    {
        $job = $jobArgs->getJob();
        $data = $job->getData();
        if (empty($data)) {
            return array('error' => 'Data empty');
        }

        /*
        $this->container->get('dbal_connection')->executeQuery('DELETE FROM s_crontab WHERE `id` = ?', [
            $job->getId()
        ]);
        */

        if (empty($data['basketId']) || empty($data['subscriptionId'])) {
            return array('error' => 'basketId or subscriptionId empty');
        }

        $basketId = $data['subscriptionId'];
        $subscriptionId = $data['subscriptionId'];

        $basket = $this->getModelManager()->getRepository(\Shopware\Models\Order\Basket::class)->find($basketId);
        if (!$basket) {
            return array('error' => 'Basket not found');
        }

        $article = $this->getModelManager()->getRepository(\Shopware\Models\Article\Article::class)->find($basket->getArticleId());
        if (!$article) {
            return array('error' => 'Article not found');
        }

        $image = $article->getImages()->first();
        $iconUrl = null;
        if ($image) {
            $iconUrl = Shopware()->Shop()->getBaseUrl() . '/' . $image->getMedia();
        }

        $config = $this->container->get('shopware.plugin.config_reader')->getByPluginName($this->getName());
        if (empty($config->channelId) || empty($config->privateApiKey)) {
            return array('error' => 'channelId or privateApiKey empty');
        }

        $title = $basket->getArticleName();
        $emoji = json_decode('"\ud83d\uded2"');
        $notificationText = $config->notificationText;
        if (empty($notificationText)) {
            $notificationText = 'Wir haben noch etwas in deinem Warenkorb gefunden.';
        }
        $body = $emoji . ' ' . $notificationText;
        $url = Shopware()->Shop()->getBaseUrl() . '/checkout/cart';

        /*
        $cart = unserialize($session['cart']);
        if (count($cart) > 1) {
            $title = get_bloginfo('name');
            $iconUrl = null;
        }
        */

        $api = new \CleverPush_Api($config->channelId, $config->privateApiKey);
        return $api->sendNotification($title, $body, $url, $iconUrl, $subscriptionId);
    }

    public function addJsFiles(\Enlight_Event_EventArgs $args)
    {
        $jsFiles = array(
            $this->getPath() . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . '_public' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'cleverpush.js'
        );

        return new ArrayCollection($jsFiles);
    }
}
