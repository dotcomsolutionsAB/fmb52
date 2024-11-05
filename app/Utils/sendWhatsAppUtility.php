<?php

namespace App\Utils;

use Illuminate\Support\Facades\Http;

class sendWhatsAppUtility 
{
    public static function sendWhatsApp($customer, $params, $media, $campaignName) 
    {
        if (env('WHATSAPP_SERVICE_ON')) 
        {

            $content = array();
            $content['messaging_product'] = "whatsapp";
            $content['to'] = $customer;
            $content['type'] = 'template';
            $content['biz_opaque_callback_data'] = 'ace_commercial';
            $content['template'] = $params;

            $token = env('WHATSAPP_API_TOKEN');

            $curl = curl_init();

            // Initialize $response to a default value
            // changes due to server issue
            $response = null;

            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://graph.facebook.com/v19.0/357370407455461/messages',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($content),
            CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$token
            ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);

        }
        return $response;
    }

    public static function sendWhatsAppInvoice($mobile, $message, $fileUrl)
    {
        $mobile = '+918777623806';
        if (env('WHATSAPP_SERVICE_ON')) 
        {
            $content = array();
            $content['messaging_product'] = "whatsapp";
            $content['to'] = $mobile;;
            $content['type'] = 'template';
            $content['biz_opaque_callback_data'] = 'ace_commercial';

            // Updated part for sending media
            $content['template'] = [
                'name' => 'your_template_name', // your predefined template name
                'language' => [
                    'code' => 'en_US'
                ],
                'components' => [
                    [
                        'type' => 'header',
                        'parameters' => [
                            [
                                'type' => 'document',
                                'document' => [
                                    'link' => $fileUrl,
                                    'filename' => 'your_document.pdf' // Optional, name displayed in the message
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'body',
                        'parameters' => [
                            [
                                'type' => 'text',
                                'text' => 'Here is your PDF document!' // Some additional text if needed
                            ]
                        ]
                    ]
                ]
            ];

            $token = env('WHATSAPP_API_TOKEN');

            $curl = curl_init();

            // Initialize $response to a default value
            // changes due to server issue
            $response = null;

            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://graph.facebook.com/v19.0/357370407455461/messages',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($content),
            CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$token
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        
        dd($response);

    }
        return $response;    
    }
}