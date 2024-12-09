<?php

namespace App\Http\Controllers;

use App\Models\Session;
use Illuminate\Http\Request;
use Gnikyt\BasicShopifyAPI\Options;
use Gnikyt\BasicShopifyAPI\BasicShopifyAPI;
use Illuminate\Support\Facades\Log;
class HelperController extends Controller
{
    public function getShop($request)
    {
        $session_obj = $request->get('shopifySession');
        if ($session_obj) {
            $session = Session::where('shop', $session_obj->getShop())->first();
        } else {
            $session = Session::first();
        }
        return $session;
    }
    public function getShopApi($shop_name)
    {

        $session = Session::where('shop', $shop_name)->first();
        // Create options for the API
        $options = new Options();
        $options->setType(true);
        $options->setVersion(env('SHOPIFY_API_VERSION'));
        $options->setApiKey(env('SHOPIFY_API_KEY'));
        $options->setApiSecret(env('SHOPIFY_API_SECRET'));
        $options->setApiPassword($session->access_token);

        // Create the client and session
        $api = new BasicShopifyAPI($options);
        $api->setSession(new \Gnikyt\BasicShopifyAPI\Session($session->shop));

        return $api;
    }
}
