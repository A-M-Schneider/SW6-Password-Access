<?php declare(strict_types=1);

namespace AmsPasswordAccess\Storefront\Page\Lock;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\HttpFoundation\Request;

class LockPageLoadedEvent extends PageLoadedEvent
{
    protected LockPage $page;

    public function __construct(LockPage $page, SalesChannelContext $salesChannelContext, Request $request)
    {
        $this->page = $page;
        parent::__construct($salesChannelContext, $request);
    }

    public function getPage(): LockPage
    {
        return $this->page;
    }
}