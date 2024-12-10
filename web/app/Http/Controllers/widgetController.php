<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Modules;
use App\Models\Session;
use Illuminate\Http\Request;
use App\Models\GeneralModules;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;


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
            if (!isset($session->shop_global_id)) {
                $this->getShopGlobalId($shop, $session);
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
                'title' => $request->title,
                'content' => $request->content,
                'bgColor' => $request->bgColor ?? '#ffffff',
                'padding' => $request->padding ?? '10px',
                'margin' => $request->margin ?? '5px',
                'fontSize' => $request->fontSize ?? '14px',
                'fontWeight' => $request->fontWeight ?? 'normal',
                'textColor' => $request->textColor ?? '#000000',
                'emailPadding' => $request->emailPadding ?? '10px',
                'buttonBgColor' => $request->buttonBgColor ?? '#000000',
                'buttonFontSize' => $request->buttonFontSize ?? '14px',
                "discount_percentage" => $request->discount_percentage ?? '10%',
            ];

            // Set variables for the GraphQL request
            $variables = [
                'metafields' => [
                    [
                        'namespace' => 'custom_widgets',
                        'key' => $generalModule->handle,
                        'ownerId' => $session->shop_global_id,
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
            $generalModule->module->save();

            // Return success response
            return response()->json(['success' => true, 'message' => 'Metafield set successfully.']);
        } catch (Exception $e) {
            // Handle any unexpected exceptions
            log::error('Exception occurred in setMetafield: ' . $e->getMessage(), ['stack' => $e->getTraceAsString()]);
            return response()->json(['error' => 'An unexpected error occurred.', 'details' => $e->getMessage()], 500);
        }
    }

    protected function getShopGlobalId($shop, $session)
    {
        try {
            // Define GraphQL query to get shop details
            $query = <<<'QUERY'
            query {
                shop {
                    id
                    name
                    email
                }
            }
        QUERY;

            // Make the API call
            $response = $shop->graph($query);

            // Log the response from the API
            log::info('Response from Get Shop Global ID: ' . json_encode($response));

            // Check for errors in the response
            if (isset($response['errors']) && $response['errors']) {
                log::error('GraphQL API Errors: ' . json_encode($response['errors']));
                return;
            }

            // Save shop global ID in session
            if (isset($response['body']->data->shop->id)) {
                $session->shop_global_id = $response['body']->data->shop->id;
                $session->save();
            }
        } catch (Exception $e) {
            // Handle any unexpected exceptions
            log::error('Exception occurred in getShopGlobalId: ' . $e->getMessage(), ['stack' => $e->getTraceAsString()]);
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
                    "ownerId" => $session->shop_global_id,
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
    public function updateImage(Request $request, $id)
    {
        $generalModule = GeneralModules::findOrFail($id);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('images/general_modules', 'public');
            $url = asset('storage/' . $path);
            $generalModule->image = asset($url);
            $generalModule->save();
        }

        return response()->json(['message' => 'Image updated successfully!', 'url' => $url]);
    }
}
