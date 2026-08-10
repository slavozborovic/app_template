<?php
namespace App\Controller;

/**
 * Main controller — customize this for your app's logic.
 *
 * Available via $this->app:
 *   $this->app->getClient()     — DreamCommerce client (OAuth or Basic Auth)
 *   $this->app->getDb()         — PDO (App Store mode; local may not need DB)
 *   $this->app->getShopData()   — shop info array
 *   $this->app->getLocale()     — locale string
 *   $this->app->getConfig()     — full config
 *   $this->app->isLocalMode()   — true when running without Shoper iframe
 */
class Index extends ControllerAbstract
{
    public function indexAction(): void
    {
        $shopData = $this->app->getShopData();

        $this->assign('shopData', $shopData);
        $this->assign('isLocalMode', $this->app->isLocalMode());
        $this->assign('appMode', $this->app->getMode());
        $this->assign('error', null);

        $this->render('index/index');
    }
}
