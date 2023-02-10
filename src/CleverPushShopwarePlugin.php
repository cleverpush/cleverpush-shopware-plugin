<?php

namespace CleverPush\CleverPushShopware;

use Doctrine\Common\Collections\ArrayCollection;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

class CleverPushShopwarePlugin extends Plugin
{
    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Frontend' => 'onPostDispatch',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_Cleverpush' => 'registerController',
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

    public function registerController(\Enlight_Event_EventArgs $args)
    {
        return $this->getPath() . '/Controllers/Frontend/Cleverpush.php';
    }

    public function onPostDispatch(\Enlight_Event_EventArgs $args)
    {
        $controller = $args->get('subject');

        $view = $controller->View();
        $view->addTemplateDir(
            $this->getPath() . '/Views'
        );

        $config = $this->container->get('shopware.plugin.config_reader')->getByPluginName($this->getName());
        $view->assign('cleverPushChannelId', $config['channelId']);
    }

    public function addJsFiles(\Enlight_Event_EventArgs $args)
    {
        $jsFiles = array(
            $this->getPath() . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . '_public' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'cleverpush.js'
        );

        return new ArrayCollection($jsFiles);
    }
}
