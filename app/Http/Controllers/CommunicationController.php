<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmailQueueModel;
use App\Models\WhatsappQueueModel;
use App\Models\PushNotificationsQueueModel;

class CommunicationController extends Controller
{
    //
     // create email queue
     public function add_email(Request $request)
     {
         $request->validate([
             'jamiat_id' => 'required|integer',
             'family_id' => 'required|integer',
             'to' => 'required|string|email',
             'cc' => 'nullable|string|email',
             'bcc' => 'nullable|string|email',
             'from' => 'required|string|email',
             'subject' => 'required|string',
             'content' => 'required|string',
             'attachment' => 'nullable|string',
             'response' => 'nullable|string',
             'status' => 'required|in:pending,sent,failed',
             'log_user' => 'required|string'
 
         ]);
     
     
         $register_email = EmailQueueModel::create([
             'jamiat_id' => $request->input('jamiat_id'),
             'family_id' => $request->input('family_id'),
             'to' => $request->input('to'),
             'cc' => $request->input('cc'),
             'bcc' => $request->input('bcc'),
             'from' => $request->input('from'),
             'subject' => $request->input('subject'),
             'content' => $request->input('content'),
             'attachment' => $request->input('attachment'),
             'response' => $request->input('response'),
             'status' => $request->input('status'),
             'log_user' => $request->input('log_user'),
         ]);
 
         unset($register_email['id'], $register_email['created_at'], $register_email['updated_at']);
     
         return isset($register_email) && $register_email !== null
         ? response()->json(['Email add to Queue successfully!', 'data' => $register_email], 201)
         : response()->json(['Failed to add to Email Queue record'], 400);
     }
 
     // create whatsapp queue
     public function add_whatsapp(Request $request)
     {
         $request->validate([
             'jamiat_id' => 'required|integer',
             'family_id' => 'required|integer',
             'group_id' => 'nullable|string',
             'callback_url' => 'nullable|string',
             'to' => 'required|string',
             'template_name' => 'required|string',
             'content' => 'nullable|string',
             'json' => 'nullable|string',
             'response' => 'nullable|string',
             'status' => 'required|in:pending,sent,failed',
             'log_user' => 'required|string'
         ]);
     
     
         $register_whatsapp = WhatsappQueueModel::create([
             'jamiat_id' => $request->input('jamiat_id'),
             'family_id' => $request->input('family_id'),
             'group_id' => $request->input('group_id'),
             'callback_url' => $request->input('callback_url'),
             'to' => $request->input('to'),
             'template_name' => $request->input('template_name'),
             'content' => $request->input('content'),
             'json' => $request->input('json'),
             'response' => $request->input('response'),
             'status' => $request->input('status'),
             'log_user' => $request->input('log_user')
         ]);
 
         unset($register_whatsapp['id'], $register_whatsapp['created_at'], $register_whatsapp['updated_at']);
     
         return isset($register_whatsapp) && $register_whatsapp !== null
         ? response()->json(['Whatsapp add to Queue successfully!', 'data' => $register_whatsapp], 201)
         : response()->json(['Failed to add to Whatsapp Queue record'], 400);
     }

      // create whatsapp queue
      public function add_push_notifications(Request $request)
      {
          $request->validate([
              'jamiat_id' => 'required|integer',
              'family_id' => 'required|integer',
              'title' => 'required|string',
              'message' => 'required|string',
              'icon' => 'nullable|string',
              'callback' => 'nullable|string',
              'status' => 'required|status',
              'response' => 'required|string',
          ]);
      
      
          $register_push_notifications = PushNotificationsQueueModel::create([
              'jamiat_id' => $request->input('jamiat_id'),
              'family_id' => $request->input('family_id'),
              'title' => $request->input('title'),
              'message' => $request->input('message'),
              'icon' => $request->input('icon'),
              'callback' => $request->input('callback'),
              'status' => $request->input('status'),
              'response' => $request->input('response'),
          ]);
  
          unset($register_push_notifications['id'], $register_push_notifications['created_at'], $register_push_notifications['updated_at']);
      
          return isset($register_whatsapp) && $register_whatsapp !== null
          ? response()->json(['Push-Notification add to Queue successfully!', 'data' => $register_whatsapp], 201)
          : response()->json(['Failed to add to Push-Notification Queue record'], 400);
      }
}
