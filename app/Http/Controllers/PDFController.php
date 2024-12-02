<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

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
}