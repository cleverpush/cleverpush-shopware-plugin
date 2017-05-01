<?php

namespace CleverPush;

use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Common\Collections\ArrayCollection;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use CleverPush\Models\QueuedBasketCheck;

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

    public function install(InstallContext $context) {
        $this->createSchema();
        $this->createCron();

        parent::install($context);
    }

    public function uninstall(UninstallContext $context) {
        $this->removeSchema();
        $this->removeCron();

        parent::uninstall($context);
    }

    private function createSchema()
    {
        $tool = new SchemaTool($this->container->get('models'));
        $classes = [
            $this->container->get('models')->getClassMetadata(QueuedBasketCheck::class)
        ];
        $tool->createSchema($classes);
    }

    private function removeSchema()
    {
        $tool = new SchemaTool($this->container->get('models'));
        $classes = [
            $this->container->get('models')->getClassMetadata(QueuedBasketCheck::class)
        ];
        $tool->dropSchema($classes);
    }

    public function createCron()
    {
        $connection = $this->container->get('dbal_connection');
        $connection->insert(
            's_crontab',
            [
                'name'             => 'CleverPushCheckBasket',
                'action'           => 'Shopware_CronJob_CleverPushCheckBasket',
                'next'             => new \DateTime(),
                'start'            => null,
                'end'              => new \DateTime(),
                '`interval`'       => '60',
                'active'           => 1,
                'pluginID'         => $this->container->get('shopware.plugin_manager')->getPluginByName($this->getName())->getId()
            ],
            [
                'next' => 'datetime',
                'end' => 'datetime'
            ]
        );
    }
    public function removeCron()
    {
        $this->container->get('dbal_connection')->executeQuery('DELETE FROM s_crontab WHERE `action` = ?', [
            'Shopware_CronJob_CleverPushCheckBasket'
        ]);
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
            $config = $this->container->get('shopware.plugin.config_reader')->getByPluginName($this->getName());
            $notificationMinutes = $config['notificationMinutes'];
            if (empty($notificationMinutes)) {
                $notificationMinutes = 30;
            } else {
                $notificationMinutes = intval($notificationMinutes);
            }

            $time = new \DateTime();
            $time->modify('+' . $notificationMinutes . ' minutes');

            $em = $this->container->get('models');
            $repository = $em->getRepository(QueuedBasketCheck::class);

            // remove existent checks
            $queuedBasketCheck = $repository->findOneBy(['subscriptionId' => $subscriptionId]);
            if ($queuedBasketCheck) {
                $em->remove($queuedBasketCheck);
                $em->flush();
            }

            $queuedBasketCheck = new QueuedBasketCheck($basketId, $subscriptionId, $time);
            $em->persist($queuedBasketCheck);
            $em->flush($queuedBasketCheck);
        }
    }

    public function onPostDispatch(\Enlight_Event_EventArgs $args)
    {
        $controller = $args->get('subject');

        $view = $controller->View();
        $view->addTemplateDir(
            $this->getPath() . '/Views'
        );

        $config = $this->container->get('shopware.plugin.config_reader')->getByPluginName($this->getName());
        $view->assign('cleverPushConfig', json_encode(['channelId' => $config['channelId']]));
    }

    public function checkBasketCron(\Shopware_Components_Cron_CronJob $jobArgs)
    {
        $em = $this->container->get('models');
        $repository = $em->getRepository(QueuedBasketCheck::class);

        $checks = $repository->findAll();

        foreach ($checks as $check) {
            // skip if time has not been reached yet
            if ($check->getTime() > new \DateTime()) {
                continue;
            }

            $basketId = $check->getBasketId();
            $subscriptionId = $check->getSubscriptionId();

            if (empty($basketId) || empty($subscriptionId)) {
                echo 'basketId or subscriptionId empty';
                return true;
            }

            $basket = $em->getRepository(\Shopware\Models\Order\Basket::class)->find($basketId);
            if (!$basket) {
                echo 'Basket not found';
                return true;
            }

            $article = $em->getRepository(\Shopware\Models\Article\Article::class)->find($basket->getArticleId());
            if (!$article) {
                echo 'Article not found';
                return true;
            }

            $shopConfig = $this->container->get('config');
            $shopHost = $shopConfig->get('host');
            $shopSecure = false;
            if (empty($shopHost)) {
                $repository = $em->getRepository('Shopware\Models\Shop\Shop');
                $shop = $repository->getActiveById(1);
                if ($shop) {
                    $shopHost = $shop->getHost();
                    $shopSecure = $shop->getSecure();
                }
            }

            $shopUrl = 'http' . ($shopSecure ? 's' : '') . '://' . $shopHost;

            $image = $article->getImages()->first();
            $iconUrl = null;
            if ($image) {
                $media = $image->getMedia();
                if ($media) {
                    $iconUrl = $shopUrl . '/' . $media->getPath();
                }
            }

            $config = $this->container->get('shopware.plugin.config_reader')->getByPluginName($this->getName());
            if (empty($config['channelId']) || empty($config['privateApiKey'])) {
                echo 'channelId or privateApiKey empty';
                return true;
            }

            $title = $basket->getArticleName();
            $emoji = json_decode('"\ud83d\uded2"');
            $notificationText = $config['notificationText'];
            if (empty($notificationText)) {
                $notificationText = 'Wir haben noch etwas in deinem Warenkorb gefunden.';
            }
            $body = $emoji . ' ' . $notificationText;
            $url = $shopUrl . '/checkout/cart';

            /*
            // not working yet, dont know how to find multiple articles in basket
            if (count($basket->articleID) > 1) {
                $title = $this->container->get('config')->get('shopName');
                $iconUrl = null;
            }
            */

            $api = new CleverPushApi($config['channelId'], $config['privateApiKey']);

            try {
                $response = $api->sendNotification($title, $body, $url, $iconUrl, $subscriptionId);
                echo $response;
            } catch (\Exception $ex) {
                echo $ex->getMessage();
            }

            $em->remove($check);
            $em->flush();
        }

        return true;
    }

    public function addJsFiles(\Enlight_Event_EventArgs $args)
    {
        $jsFiles = array(
            $this->getPath() . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . '_public' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'cleverpush.js'
        );

        return new ArrayCollection($jsFiles);
    }
}
