<?php
namespace App\Controller;

/**
 * Main controller — customize this for your app's logic.
 *
 * Available via $this->app:
 *   $this->app->getClient()   — DreamCommerce OAuth client (ready for API calls)
 *   $this->app->getDb()       — PDO database connection
 *   $this->app->getShopData() — array with shop info (id, shop, shop_url, etc.)
 *   $this->app->getLocale()   — current locale string (e.g. 'pl_PL')
 *   $this->app->getConfig()   — full config array
 */
class Index extends ControllerAbstract
{
    /**
     * Main action — entry point for your app UI.
     */
    public function indexAction(): void
    {
        $shopData = $this->app->getShopData();

        // ── Your app logic goes here ──

        // Example: Fetch products from Shoper API
        // $client   = $this->app->getClient();
        // $resource = new \DreamCommerce\ShopAppstoreLib\Resource\Product($client);
        // $result   = $resource->limit(12)->get();
        // $products = $result->getPage();

        // Assign variables to the view
        $this->assign('shopData', $shopData);
        $this->assign('error', null);

        $this->render('index/index');
    }
}
