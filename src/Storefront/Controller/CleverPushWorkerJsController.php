<?php declare(strict_types=1);

namespace CleverPush\CleverPushShopware\Storefront\Controller;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Framework\Cache\Annotation\HttpCache;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_routeScope"={"storefront"}})
 */
class CleverPushWorkerJsController extends StorefrontController
{
    private SystemConfigService $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * @HttpCache()
     *
     * @Route("/cleverpush/cleverpush-worker.js", name="frontend.example.example", methods={"GET"})
     */
    public function showCleverPushWorkerJs(): Response
    {
        $channelId = $this->systemConfigService->get('CleverPushShopwarePlugin.config.channelId');
        $workerContent = 'importScripts("https://static.cleverpush.com/channel/worker/' . $channelId . '.js");';

        return new Response(
            $workerContent,
            Response::HTTP_OK,
            ['content-type' => 'text/javascript']
        );
    }
}
