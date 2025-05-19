<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ReceiptsModel;

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
       return response($pdf->output(), 200)
    ->header('Content-Type', 'application/pdf')
    ->header('Content-Disposition', 'inline; filename="'.$filename.'"');
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
}