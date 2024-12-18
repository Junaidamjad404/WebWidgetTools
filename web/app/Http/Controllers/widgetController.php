<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Modules;
use App\Models\Session;
use App\Models\Customer;
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
        try {
            // Retrieve the shop session
            $session = $this->helperController->getShop($request);
            if (!$session) {
                throw new Exception('Invalid shop session.');
            }

            // Fetch all general modules
            $generalModules = GeneralModules::all();
            if ($generalModules->isEmpty()) {
                throw new Exception('No general modules found.');
            }

            // Fetch shop-specific modules with related general module details
            $shopModules = Modules::where('shop_id', $session->shop)
                ->with('generalModule:id,name,handle,description,image')
                ->get();

            // Check if generalModules and shopModules are synchronized
            if ($generalModules->count() == $shopModules->count()) {
                return response()->json(['modules' => $shopModules], 200);
            }

            // Sync shop modules with general modules
            foreach ($generalModules as $generalModule) {

                $exists = $shopModules->contains(function ($shopModule) use ($generalModule) {
                    return $shopModule->general_module_id === $generalModule->id;
                });

                if (!$exists) {
                    Log::info('Adding new module for shop', [
                        'shop_id' => $session->shop,
                        'general_module_id' => $generalModule->id,
                    ]);

                    Modules::create([
                        'shop_id' => $session->shop,
                        'custom_settings' => $generalModule->settings,
                        'general_module_id' => $generalModule->id,
                    ]);
                }
            }

            // Fetch updated shop modules
            $updatedShopModules = Modules::where('shop_id', $session->shop)
                ->with('generalModule:id,name,handle,description')
                ->get();

            // Return the updated shop modules
            return response()->json(['modules' => $updatedShopModules], 200);
        } catch (Exception $e) {
            // Log the exception details
            Log::error('Error in getWidget:', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);

            // Return a detailed error response
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching or updating modules.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function setMetafield(Request $request)
    {
        try {
            // Retrieve shop session and shop API instance
            $session = $this->helperController->getShop($request);
            $shop = $this->helperController->getShopApi($session->shop);

            // Check if shop global ID exists, if not, retrieve it
            if (!isset($session->app_id)) {
                $this->getAppInstalledGlobalId($shop, $session);
            }

            // Define GraphQL query for setting metafields
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

            // Log incoming request data
            log::info('Request received: ' . json_encode($request->all()));

            // Find the general module based on provided ID
            $generalModule = GeneralModules::findOrFail($request->general_module_id);
            // Prepare metafield value with defaults
            $metafieldValue = [
                "discount_price_container"=>[
                    "text"=>
                    $this->appendVariablefunc(
                        $request->discount_price_container['discount_price_container_Text'] ?? "OR IF YOU"
                    ),
                    "font_size"=>$request->discount_price_container['font_size'] ?? "1.4rem",
                    "text_color"=>$request->discount_price_container['text_color'] ?? "black",
                    "background_color" => $request->discount_price_container['background_color'] ?? "#ffffff",
                    "line_height" => $request->discount_price_container['line_height'] ?? "20px",
                    "margin_top" => $request->discount_price_container['margin_top'] ?? "10px",
                    "action"=>$request->discount_price_container['discount_price_container_action_Text'] ?? "SIGN UP FOR THE NEWSLETTER."
                ],
                "discount_widget"=>[
                    "input"=>[
                        "padding"=>$request->discount_widget['input']['padding']??"10px",
                        "font_size"=>$request->discount_widget['input']['font_size']??"1.4rem",
                        "border_radius"=>$request->discount_widget['input']['border_radius']??"1px",
                        "min_height" => $request->discount_widget['input']['min_height'] ?? "45px",
                        "width" => $request->discount_widget['input']['width'] ?? "100%",
                        "margin_top" => $request->discount_widget['input']['margin_top'] ?? "100%",
                        "place_holder"=> $request->discount_widget['input']['place_holder'] ?? "Enter your email%",
                    ],
                    "button_hover"=>[
                        "text_color"=>$request->discount_widget['button_hover']["text_color"]??"white",
                        "background_color"=>$request->discount_widget['button_hover']["background_color"]??"black",
                        "border"=>$request->discount_widget['button_hover']["border"]??"2px solid black"
                    ],
                    "button" => [
                        "min_height" =>$request->discount_widget['button']['min_height'] ?? "45px",
                        "width" => $request->discount_widget['button']['width'] ?? "100%",
                        "margin_top" => $request->discount_widget['button']['margin_top'] ?? "100%",
                        "padding"=>$request->discount_widget['button']['padding']??"13px 15px",
                        "background_color" => $request->discount_widget['button']['background_color'] ?? "black",
                        "text_color"=>$request->discount_widget['button']['text_color'] ?? "white",
                        "border"=>$request->discount_widget['button']['border']??"1px solid black",
                        "cursor" => $request->discount_widget['button']['cursor']??"pointer",
                        "font_size"=>$request->discount_widget['button']['font_size']??"1.4rem",
                        "font_weight"=>$request->discount_widget['button']['font_weight'] ?? "500",
                        "letter_spacing" => $request->discount_widget['button']['letter_spacing'] ?? "0.1rem",
                        "text" => $request->discount_widget['button']['text'] ?? "0.1rem"
                    ]
                ],
                "content"=>[
                        "text"=>$request->content['text']??"By signing up, you agree to receive commercial communications from the store. You may withdraw your consent whenever you want ",
                        "font_size"=>$request->content['font_size']??"1rem",
                        "line_height"=>$request->content['line_height']??"1.4rem",
                        "text_color" => $request->content['line_height'] ?? "black",
                        "letter_spacing" => $request->button['letter_spacing'] ?? "0.1rem",
                        "anchor_tag"=>[
                            "text"=>$request->button['anchor_tag']['text'] ?? "Privacy Policy",
                            "text_color"=>$request->button['anchor_tag']['text_color'] ?? "blue"
                        ]
                ],
                 "success"=>[
                        "margin_top"=>$request->success['margin_top']??"10px",
                        "font_size"=>$request->success['font_size']??"1.4rem",
                        "line_height"=>$request->success['line_height']??"20px",
                        "text_color"=>$request->success['text_color']??"green"
                ],
                    "error"=>[
                        "margin_top"=>$request->error['margin_top']??"10px",
                        "font_size"=>$request->error['font_size']??"1.4rem",
                        "line_height"=>$request->error['line_height']??"20px",
                        "text_color"=>$request->error['text_color']??"green"
                    ], 
                
                "discount_percentage" => $request->discount_percentage ?? '10%', 
                "status" => $request->status ?? 0 ,

            ];
            // Set variables for the GraphQL request
            $variables = [
                'metafields' => [
                    [
                        'namespace' => 'custom_widgets',
                        'key' => $generalModule->handle,
                        'ownerId' => $session->app_id,
                        'type' => 'json_string',
                        'value' => json_encode($metafieldValue),
                    ],
                ],
            ];

            // Make the API call
            $response = $shop->graph($query, $variables);

            // Log the response from the API
            log::info('Response from Set Metafield: ' . json_encode($response));
            // Check for errors in the API response
            if (isset($response['errors']) && $response['errors']) {
                log::error('GraphQL API Errors: ' . json_encode($response['errors']));
                return response()->json(['error' => 'Failed to set metafield.', 'details' => $response['errors']], 422);
            }

            // Check if there are user errors in the response
            $metafieldsSet = $response['body']->data->metafieldsSet ?? null;
            if (isset($metafieldsSet->userErrors) && count($metafieldsSet->userErrors) > 0) {
                log::error('User Errors in Set Metafield: ' . json_encode($metafieldsSet->userErrors));
                return response()->json(['error' => 'Metafield errors occurred.', 'details' => $metafieldsSet->userErrors], 422);
            }

            // Update custom settings of the general module
            log::info('Custom Settings of Module: ' . json_encode($response['body']->data->metafieldsSet->metafields[0]->value));
            $generalModule->module->custom_settings = $response['body']->data->metafieldsSet->metafields[0]->value;
            $generalModule->module->status = $metafieldValue['status'];
            $generalModule->module->save();

            // Return success response
            return response()->json(['success' => true, 'message' => 'Metafield set successfully.']);
        } catch (Exception $e) {
            // Handle any unexpected exceptions
            log::error('Exception occurred in setMetafield: ' . $e->getMessage(), ['stack' => $e->getTraceAsString()]);
            return response()->json(['error' => 'An unexpected error occurred.', 'details' => $e->getMessage()], 500);
        }
    }
    public function appendVariablefunc($text){
        // Check if the text contains {discount_code}, and if so, don't append anything
        if (preg_match('/\{discount_code\}/', $text)) {
            // Replace any variant of discount_code (e.g., {1223discount_code}, {discount_code123}) with {discount_code}
            $text = preg_replace('/\{.*discount_code\w*\}/', '{discount_code}', $text);
        } else {
            // If {discount_code} is not present, append {discount_price} after the first word
            $textParts = preg_split('/\s+/', $text, 2); // Split into first word and the rest of the sentence
            $textParts[0] .= ' {discount_price}'; // Append the placeholder {discount_price} after the first word
            $text = implode(' ', $textParts);
        }

        return $text;
    }
    protected function getAppInstalledGlobalId($shop, $session)
    {
        try {
            // Define GraphQL query to get shop details
            $query =<<<'Query'
                query {
                    currentAppInstallation {
                        id
                    }
                }
            Query;

            // Make the API call
            $response = $shop->graph($query);
            // Log the response from the API
            log::info('Response from Get App ID: ' . json_encode($response));

            // Check for errors in the response
            if (isset($response['errors']) && $response['errors']) {
                log::error('GraphQL API Errors: ' . json_encode($response['errors']));
                return;
            }

            // Save shop global ID in session
            if (isset($response['body']->data->currentAppInstallation->id)) {
                $session->app_id = $response['body']->data->currentAppInstallation->id;
                $session->save();
            }
        } catch (Exception $e) {
            // Handle any unexpected exceptions
            log::error('Exception occurred in getAppInstalledGlobalId: ' . $e->getMessage(), ['stack' => $e->getTraceAsString()]);
        }
    }


    public function deleteMetafield(Request $request)
    {
        try {
            // Retrieve shop session and shop API instance
            $session = $this->helperController->getShop($request);
            $shop = $this->helperController->getShopApi($session->shop);

            // Find the general module based on the provided ID
            $generalModule = GeneralModules::findOrFail($request->general_module_id);

            // Define the GraphQL mutation query to delete metafields
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

            // Prepare variables for the GraphQL request
            $variables = [
                "metafields" => [[
                    "ownerId" => $session->app_id,
                    'namespace' => 'custom_widgets',
                    'key' => $generalModule->handle,
                ]],
            ];

            // Make the API call
            $response = $shop->graph($query, $variables);

            // Log the response from the API
            log::info('Response from Delete Metafield: ' . json_encode($response));

            // Check if the response contains deleted metafields
            if (isset($response['body']->data->metafieldsDelete->deletedMetafields)) {
                // Reset custom settings to default
                $generalModule->module->custom_settings = $generalModule->settings;
                $generalModule->module->save();

                // Return success response
                return response()->json(['success' => true, 'message' => 'Metafield deleted successfully.']);
            }

            // Check for user errors in the response
            if (isset($response['body']->data->metafieldsDelete->userErrors)) {
                log::error('User Errors in Delete Metafield: ' . json_encode($response['body']->data->metafieldsDelete->userErrors));
                return response()->json(['error' => 'Failed to delete metafield.', 'details' => $response['body']->data->metafieldsDelete->userErrors], 422);
            }

            // Return a general error message if metafield deletion fails without clear errors
            return response()->json(['error' => 'An unexpected error occurred while deleting the metafield.'], 500);
        } catch (Exception $e) {
            // Handle any unexpected exceptions
            log::error('Exception occurred in deleteMetafield: ' . $e->getMessage(), ['stack' => $e->getTraceAsString()]);
            return response()->json(['error' => 'An unexpected error occurred.', 'details' => $e->getMessage()], 500);
        }
    }

    public function customSetting(Request $request)
    {
        // Validate incoming request to ensure module_id is provided
        $request->validate([
            'module_id' => 'required|exists:modules,id', // Ensure module_id exists in the modules table
        ]);

        // Try to find the module by its ID
        $module = Modules::find($request->module_id);

        // Check if module exists
        if ($module) {
            // Check if custom_settings are available
            if ($module->custom_settings) {
                return response()->json(['custom_settings' => $module->custom_settings]);
            } else {
                // If custom settings are missing, return a response indicating that
                return response()->json(['error' => 'Custom settings not found for this module.'], 404);
            }
        }

        // Return an error if the module was not found
        return response()->json(['error' => 'Module not found.'], 404);
    }

    public function updateModuleStatus(Request $request)
    {
        // Validate incoming request to ensure module_id is provided
        $request->validate([
            'module_id' => 'required|exists:modules,id', // Ensure module_id exists in the modules table
        ]);

        // Try to find the module by its ID
        $shopModule = Modules::find($request->module_id);

        // Check if module exists
        if ($shopModule) {
            // Toggle the status of the module
            $shopModule->status = !$shopModule->status;

            // Save the updated module
            $shopModule->save();

            // Return a successful response with the updated status
            return response()->json(['status' => $shopModule->status, 'message' => 'Module status updated successfully.']);
        }

        // Return an error if the module was not found
        return response()->json(['error' => 'Module not found.'], 404);
    }
     public function checkCustomer(Request $request)
    {
        // return ($request->all());
        $request->validate([
            'shopDomain' => 'required|string',
            'email' => 'required|email',
        ]);
        log::info('Request of checkCustomer'.json_encode($request->all()));
        $module_status = GeneralModules::where('handle','First_Sign_Up_Discount')->first()->module->status;
        if(!$module_status){
            log::info('Status of Modules is set false.');
            return response()->json(['error' => 'Status of the Modules is disable']); 
        }
        // Retrieve shop session and shop API instance
        $session =Session::where('shop',$request->shopDomain)->first();
        $shop = $this->helperController->getShopApi($session->shop);
        // Get shopDomain and email
        $shopDomain = $request->shopDomain;
        $email = $request->email;
        // Retrieve the Shopify access token (this assumes it's stored in your database)
        if (! $session ) {
            return response()->json(['error' => 'Shop not found or unauthorized'], 403);
        }

        $customer = Customer::where('shop_id', $session->shop)
            ->where('email', $email)
            ->first();

        if ($customer) {
            $customer_with_order = $customer->where('no_of_orders', '!=', 0)->get();
            if (empty($customer_with_order)) {
                return response()->json([
                    'error' => 'You are already a customer with orders.',
                    'customer' => $customer,
                ]);
            }

            return response()->json([
                'error' => 'You are already a customer.',
                'customer' => $customer,
            ]);
        }

        // If no customer is found, consider returning a default response or continue with your logic.
        Log::info('No customer found with orders for email: '.$email);
       

        return $this->getCustomer($session,$shop,$email);

    }
    public function customerCreate($shop,$customerEmail = null)
    {
        $query = <<<'QUERY'
        mutation customerCreate($input: CustomerInput!) {
            customerCreate(input: $input) {
                userErrors {
                    field
                    message
                }
                customer {
                    id 
                }
            }
        }
        QUERY;

        $variables = [
            'input' => [
                'email' => $customerEmail,
                'emailMarketingConsent' => [
                    'marketingOptInLevel' => 'CONFIRMED_OPT_IN',
                    'marketingState' => 'SUBSCRIBED',
                ],
            ],
        ];
        $response = $shop->graph($query, $variables);

        return $response;
    }

    public function getCustomer($session,$shop,$customerEmail)
    {

        $query = <<<'QUERY'
        query getCustomerByEmail($email: String!) {
            customers(first: 1, query: $email) {
                edges {
                    node {
                        id
                        email
                        emailMarketingConsent {
                            marketingOptInLevel
                        }
                    }
                }
            }
        }
    QUERY;

        $variables = [
            'email' => "email:$customerEmail",
        ];
        $response = $shop->graph($query, $variables);
        log::info('Response: '.json_encode($response));
        $customer = $response['body']->data->customers->edges;
        log::info('Customer Details: '.json_encode($customer));
        if (isset($customer) && count($customer) > 0) {
            $customerId = $customer[0]['node']['id'];

            return response()->json(['error' => 'Customer already exists.', 'customerId' => $customerId]);
        
        } else {
            // Customer found, use their details

            $newCustomer = $this->customerCreate($shop,$customerEmail);
            log::info('New Customer: '.json_encode($newCustomer));
            // Check for errors in the response
            if (isset($newCustomer['errors']) && ! empty($newCustomer['errors'])) {
                // Return the error if customer creation failed
                return response()->json(['error' => 'Customer creation failed', 'details' => $newCustomer['errors']]);
            } else {
                $customerId = $newCustomer['body']->container['data']['customerCreate']['customer']['id'];
                $discountCode = $this->createFirstOrderDiscount($shop,$customerId);
                if (isset($discountCode['errors']) && ! empty($discountCode['errors'])) {
                    // Return the error if customer creation failed
                    return response()->json(['error' => 'Customer creation failed', 'details' => $newCustomer['errors']]);
                }
            }
            Customer::create([
                'shop_id' => $session->id,
                'email' => $customerEmail,
            ]);

            return response()->json(['message' => 'New customer has been created', 'discountCode' => $discountCode]);
        }

        return $response;
    }

    public function createFirstOrderDiscount($shop,$customerId)
    {
        $query = <<<'QUERY'
            mutation discountCodeBasicCreate($basicCodeDiscount: DiscountCodeBasicInput!) {
                discountCodeBasicCreate(basicCodeDiscount: $basicCodeDiscount) {
                    codeDiscountNode {
                        codeDiscount {
                            ... on DiscountCodeBasic {
                                title
                                codes(first: 10) {
                                    nodes {
                                    code
                                    }
                                }
                                startsAt
                                customerGets {
                                    value {
                                    ... on DiscountPercentage {
                                        percentage
                                    }
                                    }
                                    items {
                                    ... on AllDiscountItems {
                                        allItems
                                    }
                                    }
                                }
                            }
                        }
                    }
                    userErrors {
                        field
                        code
                        message
                    }
                }
            }
        QUERY;
        $randomCode = $this->generateRandomCode(12); // Generate a random discount code
        log::info('CustomerID'.$customerId);
        $variables = [
            'basicCodeDiscount' => [
                'title' => '10% Off First Order',
                'code' => $randomCode, // Use the generated random code
                'startsAt' => now()->toISOString(),

                'customerSelection' => [
                    'customers' => [
                        'add' => $customerId,
                    ],
                ],
                'combinesWith' => [
                    'orderDiscounts' => true,
                ],
                'customerGets' => [
                    'value' => [
                        'percentage' => 0.1, // 10% discount
                    ],
                    'items' => [
                        'all' => true,
                    ],
                ],
                'usageLimit' => 1,
            ],
        ];

        // Log the generated discount code for debugging
        Log::info('Generated Discount Code: '.$randomCode);

        $response = $shop->graph($query, $variables);
        Log::info('Discount Code Creation Response: ', $response);

        if (isset($response['body']->container['errors']) && ! empty($response['body']->container['errors'])) {
            return response()->json(['error' => 'Discount creation failed', 'details' => $response['errors']]);
        }

        $discountCode = $response['body']->data->discountCodeBasicCreate->codeDiscountNode->codeDiscount->codes->nodes[0]->code;

        return $discountCode;
    }

    public function generateRandomCode($length = 12)
    {
        return strtoupper(substr(bin2hex(random_bytes($length)), 0, $length));
    }
}
