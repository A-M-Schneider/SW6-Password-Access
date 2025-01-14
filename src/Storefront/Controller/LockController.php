<?php declare(strict_types=1);

namespace AmsPasswordAccess\Storefront\Controller;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use AmsPasswordAccess\Storefront\Page\Lock\LockPageLoader;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * #[Route(defaults: ['_routeScope' => ['storefront']])]
 */
class LockController extends StorefrontController
{
    private LockPageLoader $lockPageLoader;
    private SystemConfigService $systemConfigService;

    public function __construct(LockPageLoader $lockPageLoader, SystemConfigService $systemConfigService)
    {
        $this->lockPageLoader = $lockPageLoader;
        $this->systemConfigService = $systemConfigService;
    }

    /**
    * #[Route(path: "/lock", name: "frontend.lock.page", methods: ["GET", "POST"])]
    */
    public function lockPage(Request $request, SalesChannelContext $context): Response
    {
        $page = $this->lockPageLoader->load($request, $context);

        $salesChannel = $context->getSaleschannel();
        
        
        //GET CMS CONTENT
        $text = $this->systemConfigService->get('AmsPasswordAccess.config.text',$salesChannel->getId());

        if($request->request->has('password')){
            //POST PWD
            $input = $request->request->get('password');

            //Config PWD
            $pwd = $this->systemConfigService->get('AmsPasswordAccess.config.password',$salesChannel->getId());


            if($input == $pwd){
                $response = new RedirectResponse("/");
                $response->headers->setCookie(new Cookie('loggedIn', '1'));
                return $response;
            }else{
                $response = new RedirectResponse("/");
                $response->headers->setCookie(new Cookie('loggedIn', '0'));
                return $response;
            }            
        }
        
        return $this->renderStorefront('@AmsPasswordAccess/storefront/page/lock.html.twig', [
            'text' => $text,
            'page' => $page
        ]);
        
        $response = new JsonResponse();
    }
}