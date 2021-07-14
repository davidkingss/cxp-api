<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ItemDefinitions;
use App\Models\TransactionLogs;
use App\Traits\magayaTrait;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\XmlController;
use Illuminate\Support\Facades\Artisan;

class ItemDefinitionsController extends Controller
{
    use magayaTrait;

    public function index(Request $request)
    {
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();
            $this->guid = $user->guid_client;
            $validatedData = $request->validate([
                'fecha_inicial' => 'required|date_format:Y-m-d',
                'fecha_final' => 'required|date_format:Y-m-d',
            ]);

            $po = ItemDefinitions::select('item_definitions.iv_data->CreatedOn as CreatedOn', 'item_definitions.iv_guid', 'item_definitions.iv_data->PartNumber as PartNumber')
                ->whereDate('item_definitions.iv_data->CreatedOn', '>=', $validatedData['fecha_inicial'])
                ->whereDate('item_definitions.iv_data->CreatedOn', '<=', $validatedData['fecha_final']);
            return $po->get();
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
                'guid' => 'required|string',
            ]);
            $po = ItemDefinitions::select('item_definitions.iv_xml')
                ->where('item_definitions.iv_guid', $request->guid);

            $po = $po->first();

            if (!$po || $po->count() == 0) {
                return response(["No se encontro la transaccion con GUID " . $request->guid], 404);
            }

            return $po;
        } else {
            return response(['Unauthorized. error Unauthorized'], 404);
        }
    }

    public function setItemDefinitions(Request $request)
    {
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {

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

            $requiredXml = ItemDefinitions::$requiredXml;
            $validateObj = XmlController::validateMinimunXml($requiredXml, $xml);
            if ($validateObj["response"] == false) {
                return response([
                    "Datos incompletos" => $validateObj["error"],
                ], 404);
            }

            $guidClient = isset($xml->Client->attributes()["GUID"]) ? end($xml->Client->attributes()["GUID"]) : "";
            if ($guidClient == "") {
                return response([
                    "Datos incompletos" => "No se encontro el atributo GUID en el nodo Client",
                ], 404);
            }

            $magClient = $this->f_GetTransaction($guidClient, "CL", true);
            if ($magClient == "NOT TRANSACTION" || (isset($magClient["response"]) && $magClient["response"] == false)) {
                return response([
                    'error' => 'No se encontro un cliente con GUID ' . $guidClient
                ], 404);
            }

            $xmlClient = XmlController::validateXmlString($magClient);
            if ($xmlClient["response"] == false) {
                return response([
                    "XML invalido" => $xmlClient["error"],
                ], 404);
            }
            $client = $xmlClient["xml"];
            $clientXml = $xml->Client;

            XmlController::append_simplexml($clientXml, $client);
            $xml = XmlController::setCreationDateXML($xml);

            $response = $this->f_SetTransaction($xml->asXML(), "IV");
            $return = isset($response["return"]) ? $response["return"] : "Error en peticion";
            LogsController::newLog(end($xml->PartNumber), $return, "IV");
            if ($return == "Error en peticion" || $return != "no_error") {
                return response([
                    "Error en el cargue a Magaya" => $response,
                ], 404);
            }

            Artisan::call("cronIV");

            $po = ItemDefinitions::select('item_definitions.iv_guid')
                ->where('item_definitions.iv_data->PartNumber', strtoupper(end($xml->PartNumber)))
                ->first();

            $guidResponse = "";
            if ($po && $po->count() > 0) {
                $guidResponse = $po->iv_guid;
            } else {
                $error = "La transacción no generó error en la creación pero no existe en Magaya, puede deberse a que se están usando códigos SKU usados en otro ItemDefinition";
                LogsController::newLog(end($xml->PartNumber), $error, "IV");
                return response([
                    "Error en el cargue a Magaya" => $error,
                ], 404);
            }

            return response([
                "Magaya response" => $return,
                "GUID" => $guidResponse,
            ], 200);
        } else {
            return response(['Unauthorized. error Unauthorized'], 404);
        }
    }

    public static function replaceAndValidateItemDefinition($xml)
    {
        $items = isset($xml->Items->Item) ? $xml->Items->Item : "";
        $response = 0;
        foreach ($xml->Items->Item as $item) {
            $response++;
            $itemDefinition = property_exists($item, "ItemDefinition") ? $item->ItemDefinition : "";
            $guid = isset($itemDefinition->attributes()["GUID"]) ? end($itemDefinition->attributes()["GUID"]) : "";
            $consulta = ItemDefinitions::select('item_definitions.iv_xml')
                ->where('item_definitions.iv_guid', $guid)
                ->where('item_definitions.iv_estado', "ACTIVO")
                ->first();
            if (!$consulta || $consulta->count() == 0) {
                return [
                    "response" => false,
                    "error" => "No se encontro ItemDefinition con GUID " . $guid,
                ];
            } else {
                $xmlItemDefinition = utf8_decode($consulta->iv_xml);
                $consulta->iv_xml = simplexml_load_string(str_replace('<?xml version="1.0" encoding="utf-8"?>', "", $xmlItemDefinition));
                XmlController::append_simplexml($itemDefinition, $consulta->iv_xml);
                $partNumber = end($consulta->iv_xml->PartNumber);
                $partNumber = $item->addChild("PartNumber", $partNumber);
            }
        }
        return [
            "response" => true,
            "xml" => $xml,
        ];
    }
}
