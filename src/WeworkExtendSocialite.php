<?php

namespace SocialiteProviders\Wework;

use SocialiteProviders\Manager\SocialiteWasCalled;

class WeworkExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('wework', __NAMESPACE__.'\Provider');
    }
}
