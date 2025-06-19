<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use App\Http\Controllers\HubController;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;


use App\Models\UploadModel;
use Illuminate\Support\Facades\Storage;

use Illuminate\Http\Request;
use App\Models\CounterModel;
use App\Models\AdvanceReceiptModel;
use App\Models\ExpenseModel;
use App\Models\PaymentsModel;
use App\Models\ReceiptsModel;
use App\Models\User;
use App\Models\WhatsAppQueue;
use App\Models\WhatsappQueueModel;
use Auth;
use App\Models\CurrencyModel;

class AccountsController extends Controller
{
    public function user()
    {
        return $this->belongsTo(User::class, 'jamiat_id', 'jamiat_id');
    }
    //
    // create
    public function register_counter(Request $request)
    {
        $request->validate([
            'jamiat_id' => 'required|integer',
            'sector' => 'nullable|integer',
            'type' => 'required|string|max:50',
            'year' => 'required|string|max:10',
            'value' => 'required|integer',
        ]);

        $register_counter = CounterModel::create([
            'jamiat_id' => $request->input('jamiat_id'),
            'sector' => $request->input('sector'),
            'type' => $request->input('type'),
            'year' => $request->input('year'),
            'value' => $request->input('value'),
        ]);

        unset($register_counter['id'], $register_counter['created_at'], $register_counter['updated_at']);

        return $register_counter
            ? response()->json(['message' => 'Counter record created successfully!', 'data' => $register_counter], 201)
            : response()->json(['message' => 'Failed to create counter record!'], 400);
    }

    // view
    public function all_counter()
    {
        $get_all_counters = CounterModel::select('jamiat_id', 'sector', 'type', 'year', 'value')->get();

        return $get_all_counters->isNotEmpty()
            ? response()->json(['message' => 'Counter records fetched successfully!', 'data' => $get_all_counters], 200)
            : response()->json(['message' => 'No counter records found!'], 404);
    }

    // update
    public function update_counter(Request $request, $id)
    {
        $get_counter = CounterModel::find($id);

        if (!$get_counter) {
            return response()->json(['message' => 'Counter record not found!'], 404);
        }

        $request->validate([
            'jamiat_id' => 'required|integer',
            'sector' => 'nullable|integer',
            'type' => 'required|string|max:50',
            'year' => 'required|string|max:10',
            'value' => 'required|integer',
        ]);

        $update_counter_record = $get_counter->update([
            'jamiat_id' => $request->input('jamiat_id'),
            'sector' => $request->input('sector'),
            'type' => $request->input('type'),
            'year' => $request->input('year'),
            'value' => $request->input('value'),
        ]);

        return ($update_counter_record == 1)
            ? response()->json(['message' => 'Counter record updated successfully!', 'data' => $update_counter_record], 200)
            : response()->json(['No changes detected!'], 304);
    }

    // delete
    public function delete_counter($id)
    {
        $delete_counter = CounterModel::where('id', $id)->delete();

        return $delete_counter
            ? response()->json(['message' => 'Counter record deleted successfully!'], 200)
            : response()->json(['message' => 'Counter record not found!'], 404);
    }

   public function addToWhatsAppQueue($receipt, $pdfUrl)
    {
        // Define the prefix for the full file URL
        $filePrefix = 'https://api.fmb52.com/storage/1/receipts/';
        
        // Generate the full URL for the PDF
        $fullPdfUrl = $filePrefix . $pdfUrl.'.pdf';
    
        // Fetch the name from the t_jamiat table based on jamiat_id
        $jamiatName = DB::table('t_jamiat')
            ->where('id', $receipt->jamiat_id)
            ->value('name'); // Fetch only the name
    
        // Prepare WhatsApp template content
        $templateContent = [
            'name' => 'fmb_receipt_create',
            'language' => ['code' => 'en'],
            'components' => [
                [
                    'type' => 'header',
                    'parameters' => [
                        [
                            'type' => 'document',
                            'document' => [
                                'link' => $fullPdfUrl, // Add the full PDF URL
                                'filename' => "{$receipt->receipt_no}.pdf", // Set the filename
                            ],
                        ],
                    ],
                ],
                [
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $receipt->name], // {{1}} - Contributor's Name
                        ['type' => 'text', 'text' => number_format($receipt->amount, 2)], // {{2}} - Contribution Amount
                        ['type' => 'text', 'text' => $jamiatName], // {{3}} - Receipt Number
                    ],
                ],
            ],
        ];
    
        // Get the authenticated user
        
    
        
        // Insert into WhatsApp queue table
        WhatsappQueueModel::create([
            'jamiat_id' => $receipt->jamiat_id,
            'group_id' => 'receipt_' . uniqid(),
            'callback_data' => 'receipt_' . $receipt->receipt_no,
            'recipient_type' => 'individual',
            'to' => '917439515253', // Use the mobile number of the recipient
            'template_name' => 'fmb_receipt_created',
            'content' => json_encode($templateContent), // Encode the content as JSON
            'file_url' => $fullPdfUrl, // Attach the full PDF URL
            'status' => 0, // Pending
            'log_user' => $jamiatName, // Log the name fetched from t_jamiat
            'created_at' => now(), // Current timestamp for creation
            'updated_at' => now(), // Current timestamp for updates
        ]);

          WhatsappQueueModel::create([
            'jamiat_id' => $receipt->jamiat_id,
            'group_id' => 'receipt_' . uniqid(),
            'callback_data' => 'receipt_' . $receipt->receipt_no,
            'recipient_type' => 'individual',
            'to' => '918961043773', // Use the mobile number of the recipient
            'template_name' => 'fmb_receipt_created',
            'content' => json_encode($templateContent), // Encode the content as JSON
            'file_url' => $fullPdfUrl, // Attach the full PDF URL
            'status' => 0, // Pending
            'log_user' => $jamiatName, // Log the name fetched from t_jamiat
            'created_at' => now(), // Current timestamp for creation
            'updated_at' => now(), // Current timestamp for updates
        ]);
    }
    public function fetchCurrencies(Request $request)
    {
        // Fetch all currencies, optionally filter by country_name or currency_code
        $currencies = CurrencyModel::query();

        // Apply filters if provided
        if ($request->has('country_name')) {
            $currencies->where('country_name', 'LIKE', '%' . $request->country_name . '%');
        }

        if ($request->has('currency_code')) {
            $currencies->where('currency_code', $request->currency_code);
        }

        // Paginate or fetch all currencies
        $result = $currencies->get();

        return response()->json([
            'message' => 'Currencies fetched successfully',
            'data' => $result,
        ], 200);
    }
    

   public function allBanks()
{
    $banks = [
        ['name' => 'State Bank of India', 'code' => 'SBI'],
        ['name' => 'HDFC Bank', 'code' => 'HDFC'],
        ['name' => 'ICICI Bank', 'code' => 'ICIC'],
        ['name' => 'Axis Bank', 'code' => 'UTIB'],
        ['name' => 'Punjab National Bank', 'code' => 'PUNB'],
        ['name' => 'Bank of Baroda', 'code' => 'BARB'],
        ['name' => 'Canara Bank', 'code' => 'CNRB'],
        ['name' => 'Kotak Mahindra Bank', 'code' => 'KKBK'],
        ['name' => 'IndusInd Bank', 'code' => 'INDB'],
        ['name' => 'Yes Bank', 'code' => 'YESB'],
        ['name' => 'Union Bank of India', 'code' => 'UBIN'],
        ['name' => 'IDBI Bank', 'code' => 'IBKL'],
        ['name' => 'Bank of India', 'code' => 'BKID'],
        ['name' => 'Central Bank of India', 'code' => 'CBIN'],
        ['name' => 'Punjab & Sind Bank', 'code' => 'PSIB'],
        ['name' => 'UCO Bank', 'code' => 'UCBA'],
        ['name' => 'Indian Bank', 'code' => 'IDIB'],
        ['name' => 'South Indian Bank', 'code' => 'SIBL'],
        ['name' => 'Federal Bank', 'code' => 'FDRL'],
        ['name' => 'Andhra Bank', 'code' => 'ANDB'],
        ['name' => 'Bank of Maharashtra', 'code' => 'MAHB'],
        ['name' => 'Corporation Bank', 'code' => 'CORP'],
        ['name' => 'Dena Bank', 'code' => 'BKDN'],
        ['name' => 'IDFC First Bank', 'code' => 'IDFB'],
        ['name' => 'Jammu & Kashmir Bank', 'code' => 'JAKA'],
        ['name' => 'Karnataka Bank', 'code' => 'KARB'],
        ['name' => 'Karur Vysya Bank', 'code' => 'KVBL'],
        ['name' => 'Lakshmi Vilas Bank', 'code' => 'LAVB'],
        ['name' => 'Oriental Bank of Commerce', 'code' => 'ORBC'],
        ['name' => 'Punjab National Bank', 'code' => 'PUNB'],
        ['name' => 'Syndicate Bank', 'code' => 'SYNB'],
        ['name' => 'Tamilnad Mercantile Bank', 'code' => 'TMBL'],
        ['name' => 'Telangana Grameena Bank', 'code' => 'TGBK'],
        ['name' => 'The Catholic Syrian Bank', 'code' => 'CSBK'],
        ['name' => 'Yes Bank', 'code' => 'YESB'],
        // Add any other known banks here...
    ];

    return response()->json([
        'code' => 200,
        'status' => true,
        'message' => 'List of Indian banks',
        'data' => $banks,
    ]);
    }
  
}
