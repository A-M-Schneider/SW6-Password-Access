<?php declare(strict_types=1);

namespace AmsPasswordAccess\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Framework\Routing\Router;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Shopware\Storefront\Page\GenericPageLoadedEvent;
use Shopware\Storefront\Framework\Cache\Event\HttpCacheHitEvent;
use Shopware\Core\Framework\Struct\ArrayEntity;
use Shopware\Core\Framework\Routing\RequestContextResolverInterface;
use Shopware\Core\PlatformRequest;

class Lock implements EventSubscriberInterface
{
    private SystemConfigService $systemConfigService;
    private Router $router;

    private $matcher;
    private RequestContextResolverInterface $contextResolver;

    /**
     * @param UrlMatcherInterface|RequestMatcherInterface $matcher
     */
    public function __construct(
        SystemConfigService $systemConfigService, 
        Router $router,
        $matcher,
        RequestContextResolverInterface $contextResolver
    )
    {
        $this->systemConfigService = $systemConfigService;
        $this->router = $router;

        $this->matcher = $matcher;
        $this->contextResolver = $contextResolver;
    }
    
    public static function getSubscribedEvents(): array
    {
        return [
            GenericPageLoadedEvent::class => 'onGenericPageLoaded',
            HttpCacheHitEvent::class => 'onCacheHitEventLoaded'
        ];
    }

    public function onGenericPageLoaded(GenericPageLoadedEvent $event): void
    {
        $this->checkLoggedIn($event);
    }

    public function onCacheHitEventLoaded(HttpCacheHitEvent $event): void
    {
        $this->checkLoggedIn($event);
    }

    private function checkLoggedIn($event){
        $request    = $event->getRequest();
        $route      = $request->attributes->get('_route');
        $cookies    = $request->cookies;

        if (!$cookies->has('loggedIn')){
            $this->redirect($event, $route);
        }else{
            if($cookies->get('loggedIn') != '1'){
                $this->redirect($event, $route);
            }
        }
    }

    private function redirect($event, $route = ''){        
        if($route != 'frontend.lock.page' ){
            $pageUnlocked = $this->customRouteUnblock($event);
            if(!$pageUnlocked){
                $response = new RedirectResponse($this->router->generate('frontend.lock.page'), 302);
                $response->send();
            }

            /*
                 $array = ['hideMenu' => '1'];
                $event->getPage()->addExtension("AmsPasswordAccess", new ArrayEntity($array));
                return true;
            */
        }
    }

    private function customRouteUnblock($event){
        if ($this->matcher instanceof RequestMatcherInterface) {
            $parameters = $this->matcher->matchRequest($event->getRequest());
        } else {
            $parameters = $this->matcher->match($event->getRequest()->getPathInfo());
        }
        $request = $event->getRequest();
        $request->attributes->add($parameters);
        $this->contextResolver->resolve($request);

        $context = $event->getRequest()->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);
        $salesChannelId = $context->getSalesChannelId();

        $exampleConfig = trim($this->systemConfigService->get('AmsPasswordAccess.config.exclude', $salesChannelId));
        
        if($exampleConfig != ""){
            $routes = explode(PHP_EOL,$exampleConfig);
            $currentRoute = $event->getRequest()->attributes->get('navigationId');
            
            if($currentRoute != null){
                if(in_array($currentRoute, $routes)){
                    return true;
                }
            }
        }
        return false;
    }
}