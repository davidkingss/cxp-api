<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use App\Http\Controllers\ItemDefinitionsController;
use App\Traits\magayaTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;

class PurchaseOrderController extends Controller
{
    use magayaTrait;

    public function index(Request $request)
    {
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();
            $this->guid = $user->guid_client;
            $validatedData = $request->validate([
                'fecha_inicial' => 'required|date_format:Y-m-d',
                'fecha_final' => 'required|date_format:Y-m-d'
            ]);
            $po = PurchaseOrder::select('purchase_orders.po_guid', 'purchase_orders.po_data->CreatedOn as po_fecha_creacion', 'purchase_orders.po_data->Number as po_number')
                ->whereDate('purchase_orders.po_data->CreatedOn', '>=', $validatedData['fecha_inicial'])
                ->whereDate('purchase_orders.po_data->CreatedOn', '<=', $validatedData['fecha_final'])
                ->where(function ($q) {
                    $q->where('purchase_orders.po_data->Buyer->attributes->GUID',  $this->guid)
                        ->orWhere('purchase_orders.po_data->Seller->attributes->GUID',  $this->guid)
                        ->orWhere('purchase_orders.po_data->Consignee->attributes->GUID',  $this->guid)
                        ->orWhere('purchase_orders.po_data->BillingClient->attributes->GUID',  $this->guid);
                });
            $response = $po->get();
            return $response->count() > 0 ? response([$response]) : response(['No se encontraron registros'], 404);
        } else {
            return response(['Unauthorized. error Unauthorized'], 404);
        }
    }

    public function getTransaction(Request $request)
    {
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();
            $this->guid = $user->guid_client;
            $request->validate([
                'guid' => 'required|string|exists:purchase_orders,po_guid',
            ]);

            $po = PurchaseOrder::select('purchase_orders.po_data')
                ->where('purchase_orders.po_guid', $request->guid)
                ->where(function ($q) {
                    $q->where('purchase_orders.po_data->Buyer->attributes->GUID',  $this->guid)
                        ->orWhere('purchase_orders.po_data->Seller->attributes->GUID',  $this->guid)
                        ->orWhere('purchase_orders.po_data->Consignee->attributes->GUID',  $this->guid)
                        ->orWhere('purchase_orders.po_data->BillingClient->attributes->GUID',  $this->guid);
                });


            $po = $po->first();

            if (!$po || $po->count() == 0) {
                return ["No se encontro la transaccion con GUID " . $request->guid];
            }

            $po = $this->object_to_array(json_decode($po->po_data));

            unset($po["Charges"]);
            unset($po["Events"]);
            unset($po["CustomFields"]);
            unset($po["DestinationAgentName"]);

            return $po;
        } else {
            return response(['Unauthorized. error Unauthorized'], 404);
        }
    }

    public function setPurChaseOrder(Request $request)
    {
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();
            $entity = $user->xml_entity;
            $this->guid = $user->guid_client;
            $request->validate([
                'xml' => 'required|string',
            ]);

            $xml = XmlController::validateXmlString($request->xml);
            if ($xml["response"] == false) {
                return response([
                    "XML invalido" => $xml["error"],
                ], 404);
            }
            $xml = $xml["xml"];

            $buyer = XmlController::validateXmlString("<Buyer>" . $entity . "</Buyer>");
            if ($buyer["response"] == false) {
                return response([
                    "XML invalido" => $buyer["error"],
                ], 404);
            }
            $buyer = end($buyer["xml"]->Name);

            $xml->addChild("BuyerName", $buyer);

            $xml = ItemDefinitionsController::replaceAndValidateItemDefinition($xml);
            if($xml["response"] == false){
                return response(["Error en reemplazo de ItemDefinition", $xml["error"]], 404);
            }
            $xml = $xml["xml"];

            $xml = XmlController::addCustomFieldFromAPI($xml);
            $xml = XmlController::setCreationDateXML($xml);

            $xmlString = html_entity_decode($xml->asXml(), ENT_NOQUOTES, 'UTF-8');
            $xmlString = str_replace("<nueva dirección>", "nueva dirección", $xmlString);

            $response = $this->f_SetTransaction($xmlString, "PO");
            $return = isset($response["return"]) ? $response["return"] : "Error en peticion";
            LogsController::newLog(end($xml->Number), $return, "PO");
            if($return == "Error en peticion" || $return != "no_error"){
                return response([
                    "Error en el cargue a Magaya" => $response,
                ], 404);
            }

            Artisan::call("cronPO");

            return response([
                ["Magaya response" => $return]
            ], 200);
            
        } else {
            return response(['Unauthorised.', ['error' => 'Unauthorised']]);
        }
    }
}
