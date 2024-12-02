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
    public function printReceipt($id)
    {
        // Fetch the receipt data by ID
        $receipt = ReceiptsModel::findOrFail($id);

        // Convert the amount to words
        $amountInWords = $this->convertNumberToWords($receipt->amount);

        // Prepare data for the PDF
        $data = [
            'header_image' => public_path('images/logo.png'), // Replace with the actual header image path
            'receipt' => $receipt,
            'amount_in_words' => $amountInWords,
        ];

        // Load the Blade view and generate the PDF
        $pdf = Pdf::loadView('receipt_template', $data)
                  ->setPaper('a5', 'portrait'); // A5 size paper

        // Stream the PDF in the browser or force a download
        return $pdf->download('receipt_' . $receipt->receipt_no . '.pdf');
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