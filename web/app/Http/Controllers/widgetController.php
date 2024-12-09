<?php

namespace App\Http\Controllers;

use App\Models\Modules;
use App\Models\Session;
use Illuminate\Http\Request;
use App\Models\GeneralModules;
use Illuminate\Support\Facades\Log;


class widgetController extends Controller
{
    protected $helperController;

    public function __construct(HelperController $helperController)
    {
        $this->helperController = $helperController;
    }
    public function getWidget(Request $request)
    {
        $session = $this->helperController->getShop($request);
        $generalModules = GeneralModules::all(); // Fetch all general modules

        $shopModules=Modules::where('shop_id',$session->shop)->get();

       
        if ($generalModules->count() == $shopModules->count()) {
   
            return response()->json(['modules' => $shopModules]);
        } else {
            foreach ($generalModules as $generalModule) {

                $exists = $shopModules->contains(function ($shopModule) use ($generalModule) {
                    return $shopModule->general_module_id === $generalModule->id;
                });
                if (!$exists) {
                    Modules::create([
                        'shop_id' => $session->shop,
                        'custome_settings' => $generalModule->settings,
                        'general_module_id' => $generalModule->id,
                    ]);
                }
            }

            // Fetch updated shop modules
            $updatedShopModules = Modules::where('shop_id', $session->shop)->get();

            // Return the updated shop modules
            return response()->json(['modules' => $updatedShopModules]);
        }
    }
    
    public function setMetafield(Request $request){
        $session = $this->helperController->getShop($request);
        $shop=$this->helperController->getShopApi($session->shop);
        if(isset($session->shop_global_id)){
            $this->getShopGlobalId($shop,$session);
        }
    
        $query = <<<'QUERY'
            mutation MetafieldsSet($metafields: [MetafieldsSetInput!]!) {
                metafieldsSet(metafields: $metafields) {
                    metafields {
                        id
                        key
                        namespace
                        value
                        createdAt
                        updatedAt
                    }
                    userErrors {
                        field
                        message
                        code
                    }
                }
            }
        QUERY;
        log::info('Request::'.json_encode($request->all()));
        $generalModule= GeneralModules::findOrFail($request->general_module_id);
        $variables = [
            'metafields' => [
                [
                    'namespace' => 'custom_widgets',
                    'key' => "Header_widget",
                    'ownerId' => $session->shop_global_id, 
                    'type' => 'json_string', 
                    'value' => json_encode([ 
                        'title' => 'Example Widget',
                        'content' => 'This is widget content',
                        'bgColor' => '#ededed',
                    ]),
                ],
            ],
        ];

        $response=$shop->graph($query,$variables);
        log::info('Response of the Set metafield: '.json_encode($response));

        if($response['errors']==false){
            log::info('Custom Settings of Module'.json_encode($response['body']->data->metafieldsSet->metafields[0]->value));
            $generalModule->module->custom_settings=$response['body']->data->metafieldsSet->metafields[0]->value;
            $generalModule->module->save();
        }
        
        return response()->json(['response'=>$response]);
    }
    protected function getShopGlobalId($shop,$session){
        
        $query = <<<'QUERY'
            query {
                shop {
                        id
                        name
                        email
                        
                    
                }
            }
        QUERY;
        $response=$shop->graph($query);
        Log::info('Response to get the response: '.json_encode($response));
        if( isset($response['errors']) && $response['errors']===false){
            $session->shop_global_id = $response['body']->data->shop->id;
            $session->save();
        }
    }
    public function deleteMetafield(Request $request){
    

        $session = $this->helperController->getShop($request);
        $shop = $this->helperController->getShopApi($session->shop);
        $generalModule = GeneralModules::findOrFail($request->general_module_id);

        $query = <<<'QUERY'
        mutation MetafieldsDelete($metafields: [MetafieldIdentifierInput!]!) {
            metafieldsDelete(metafields: $metafields) {
            deletedMetafields {
                key
                namespace
                ownerId
            }
            userErrors {
                field
                message
            }
            }
        }
        QUERY;

        $variables = [
            "metafields" => [[
                "ownerId"=> $session->shop_global_id,
                'namespace' => 'custom_widgets',
                'key' => $generalModule->handle,
            ]],
        ];
        $response = $shop->graph($query, $variables);
        log::info('Response of the Delete metafield: ' . json_encode($response));
        if(isset($response['body']->data->metafieldsDelete->deletedMetafields)){
            $generalModule->module->custom_settings=$generalModule->settings;
            $generalModule->module->save();
        }
        return response()->json(['response' => $response]);

    }
    public function customSetting(Request $request){
        $custom_settings = Modules::findOrFail($request->module_id)->custom_settings;
        if($custom_settings){
            return response()->json(['custom_settings'=>$custom_settings]);
        }

    }
    
}
