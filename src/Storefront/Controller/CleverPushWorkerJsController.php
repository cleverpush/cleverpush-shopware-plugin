<?php declare(strict_types=1);

namespace CleverPush\CleverPushShopware\Storefront\Controller;

use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Framework\Routing\StorefrontRouteScope;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: [PlatformRequest::ATTRIBUTE_ROUTE_SCOPE => [StorefrontRouteScope::ID]])]
class CleverPushWorkerJsController extends StorefrontController
{
    private SystemConfigService $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    #[Route(path: '/cleverpush-worker.js', name: 'frontend.cleverpush.worker', defaults: ['_httpCache' => true], methods: ['GET'])]
    public function showCleverPushWorkerJs(): Response
    {
        $channelId = trim($this->systemConfigService->getString('CleverPushShopwarePlugin.config.channelId'));

        $content = 'console.log(\'CleverPush Channel ID is not configured.\');';
        if ($channelId !== '' && $channelId !== '0') {
            $content = \sprintf(
                'importScripts(\'https://static.cleverpush.com/channel/worker/%s.js\' + self.location.search);',
                $channelId
            );
        }

        return new Response($content, Response::HTTP_OK, ['content-type' => 'text/javascript']);
    }
}
