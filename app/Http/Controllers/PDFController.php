<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ReceiptsModel;
use Illuminate\Support\Facades\Storage;

class PDFController extends Controller
{
    public function generatePDF()
    {
        $data = [
            'title' => 'Welcome to Laravel PDF Generation',
            'date' => date('m/d/Y'),
        ];

        $pdf = Pdf::loadView('pdf_template', $data);

        return $pdf->download('document.pdf'); // Change to stream() to display in the browser
    }
    // public function printReceipt($id)
    // {
    //     // Fetch the receipt data by ID
    //     $receipt = ReceiptsModel::findOrFail($id);

    //     // Convert the amount to words
    //     $amountInWords = $this->convertNumberToWords($receipt->amount);

    //     // Prepare data for the PDF
    //     $data = [
    //         'background' => public_path('images/receipt_bg.jpg'), // Replace with the actual header image path
    //         'receipt' => $receipt,
    //         'amount_in_words' => $amountInWords,
    //     ];
    //     $filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', 'receipt_' . $receipt->receipt_no) . '.pdf';
    //     // Load the Blade view and generate the PDF
    //     $pdf = Pdf::loadView('receipt_template', $data)
    //     ->setPaper('a5', 'landscape'); // Land

    //     // Stream the PDF in the browser or force a download
    //     return $pdf->download($filename);
    // }

    public function printReceipt($id)
    {
        // Fetch the receipt data by ID
       $receipt = ReceiptsModel::where('hashed_id', $id)->firstOrFail();

        // Convert the amount to words
        $amountInWords = $this->convertNumberToWords($receipt->amount);

        // Prepare data for the PDF
        $data = [
            'background' => public_path('images/receipt_bg.jpg'), // full path to background image
            'receipt' => $receipt,
            'amount_in_words' => $amountInWords,
        ];

        $filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', 'receipt_' . $receipt->receipt_no) . '.pdf';

        // Load the Blade view and generate the PDF
        $pdf = Pdf::loadView('receipt_template', $data)
            ->setPaper('a5', 'portrait');  // change to portrait if needed

        // Stream the PDF to browser (opens inline)
       return $pdf->stream($filename);
    }


    /**
     * Convert a number into words.
     *
     * @param int $number
     * @return string
     */
    private function convertNumberToWords($number)
    {
        $formatter = new \NumberFormatter("en", \NumberFormatter::SPELLOUT);
        return ucwords($formatter->format($number));
    }

public function generateReceiptPdfContent($hashed_ids)
{
    $hashedIdArray = explode(',', $hashed_ids);
   

    $pdfContents = '';

    foreach ($hashedIdArray as $hashed_id) {
        $receipt = ReceiptsModel::where('hashed_id', trim($hashed_id))->first();

        if ($receipt) {
            $amountInWords = $this->convertNumberToWords($receipt->amount);

            $data = [
                'background' => public_path('images/receipt_bg.jpg'),
                'receipt' => $receipt,
                'amount_in_words' => $amountInWords,
            ];

            $pdfContents .= view('receipt_template', $data)->render();
        }
    }

    if (!$pdfContents) {
        return response()->json(['message' => 'No valid receipts found.'], 404);
    }

    // Create combined PDF
    $pdf = Pdf::loadHTML($pdfContents)->setPaper('a5', 'portrait');

    $filename = 'merged_receipt_' . time() . '.pdf';
    $path = 'receipts/' . $filename;

    Storage::disk('public')->put($path, $pdf->output());

    $downloadUrl = asset('storage/' . $path);

    return response()->json([
        'message' => 'Merged PDF generated successfully.',
        'url' => $downloadUrl
    ], 200);
}
}