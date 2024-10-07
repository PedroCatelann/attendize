<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http; // Para fazer requisições HTTP
use Illuminate\Support\Facades\Validator;
use GuzzleHttp\Client;

class AsaasClientController extends Controller
{
    public function create(Request $request)
    {
        // Validação dos dados do cliente
        $validator = Validator::make($request->all(), [
            'order_first_name' => 'required|string|max:255',            
            'order_email' => 'required|email|max:255', 
            'cpfCnpj' => 'required|string|max:255',                       
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $client = new Client();
        
        // Dados do cliente
        $data = [
            'name' => $request->order_first_name,
            'cpfCnpj' => $request->cpfCnpj,
            'email' => $request->order_email,
        ];

        // Chamada para a API do Asaas
        try {
            $response = $client->request('POST', 'https://sandbox.asaas.com/api/v3/customers', [
                'body' => json_encode($data),
                'headers' => [
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                    'access_token' => env('ASAAS_API_KEY'),
                ],
            ]);

            // Verifica a resposta da API
            return 'legal';

        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
