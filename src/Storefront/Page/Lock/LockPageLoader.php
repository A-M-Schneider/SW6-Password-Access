<?php declare(strict_types=1);

namespace AmsPasswordAccess\Storefront\Page\Lock;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\GenericPageLoaderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Storefront\Page\MetaInformation;
use Shopware\Core\SalesChannelRequest;
use Symfony\Contracts\Translation\TranslatorInterface;

class LockPageLoader
{
    private GenericPageLoaderInterface $genericPageLoader;
    private EventDispatcherInterface $eventDispatcher;
    private TranslatorInterface $translatorInterface;

    public function __construct(GenericPageLoaderInterface $genericPageLoader, EventDispatcherInterface $eventDispatcher, TranslatorInterface $translatorInterface)
    {
        $this->genericPageLoader = $genericPageLoader;
        $this->eventDispatcher = $eventDispatcher;
        $this->translatorInterface = $translatorInterface;
    }

    public function load(Request $request, SalesChannelContext $context): LockPage
    {
        $page = $this->genericPageLoader->load($request, $context);
        $page = LockPage::createFrom($page);

        $page->setMetaInformation((new MetaInformation())->assign([
            'revisit' => '15 days',
            'robots' => 'index,follow',
            'xmlLang' => $request->attributes->get(SalesChannelRequest::ATTRIBUTE_DOMAIN_LOCALE) ?? '',
            'metaTitle' => $this->translatorInterface->trans('title'),
        ]));

        $this->eventDispatcher->dispatch(
            new LockPageLoadedEvent($page, $context, $request)
        );

        return $page;
    }
}