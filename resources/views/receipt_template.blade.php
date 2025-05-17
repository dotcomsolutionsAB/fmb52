<!DOCTYPE html>
<html>
<head>
    <title>Receipt</title>
    <style>
        /* A5 page size in mm for print */
        @page {
            size: A5 landscape;
            margin: 0;
        }

        /* Reset */
        html, body {
            margin: 0;
            padding: 0;
            height: 148mm;  /* A5 height */
            width: 210mm;   /* A5 width */
            font-family: Arial, sans-serif;
        }

        body {
            /* Background image set here */
            background-image: url('{{ $background }}');
            background-size: contain;   /* Cover entire page */
            background-position: center center;
            background-repeat: no-repeat;

            /* Padding inside the page for content */
            padding: 20mm 15mm;
            box-sizing: border-box;
            position: relative;
        }

        /* Header styling */
        .header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            background: transparent;
        }
        /* Remove inline <img> because background image is already set on body */
        /* Or if you still want the logo inside content, keep but no conflict */

        .header img {
            width: 60px; /* Adjust size */
            margin-right: 10px;
        }
        .header-title {
            font-size: 16px;
            font-weight: bold;
            color: #000;
        }
        .header-subtitle {
            font-size: 10px;
            margin-top: -5px;
            color: #000;
        }
        .header-ac {
            font-size: 12px;
            margin-top: -5px;
            color: #000;
        }

        .content table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
            background: transparent;
            color: #000;
        }
        .content th, .content td {
            text-align: left;
            padding: 5px;
        }

        .footer {
            position: fixed;
            bottom: 5mm;
            width: calc(100% - 30mm);
            font-size: 10px;
            text-align: center;
            border-top: 1px solid #000;
            padding-top: 5px;
            background: transparent;
            color: #000;
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="header-title">DAWOODI BOHRA JAMAAT TRUST (KOLKATA)</div>
            <div class="header-subtitle">
                Registered with the Additional Registrar of Assurances - III, as Deed No IV-00429 of 2011 (Kolkata)
            </div>
            <div class="header-ac">A/c. FAIZ UL MAWAID IL BURHANIYAH</div>
        </div>
    </div>

    <div class="content">
        <table>
            <tr>
                <th>Date:</th>
                <td>{{ \Carbon\Carbon::parse($receipt->date)->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <th>Receipt No:</th>
                <td>{{ $receipt->receipt_no }}</td>
            </tr>
            <tr>
                <th>Year:</th>
                <td>{{ $receipt->year }}</td>
            </tr>
            <tr>
                <th>Name:</th>
                <td>{{ $receipt->name }} ({{ $receipt->folio_no }})</td>
            </tr>
            <tr>
                <th>ITS No:</th>
                <td>{{ $receipt->its }}</td>
            </tr>
            <tr>
                <th>Mohalla:</th>
                <td>{{ $receipt->sector }}</td>
            </tr>
            <tr>
                <th>Amount:</th>
                <td>Rs. {{ number_format($receipt->amount, 2) }} ({{ $amount_in_words }} Only)</td>
            </tr>
            <tr>
                <th>Paid By:</th>
                <td>{{ ucfirst($receipt->mode) }}</td>
            </tr>
            @if($receipt->mode == 'cheque')
                <tr>
                    <th>Cheque No:</th>
                    <td>{{ $receipt->cheque_no }}</td>
                </tr>
                <tr>
                    <th>Cheque Date:</th>
                    <td>{{ \Carbon\Carbon::parse($receipt->cheque_date)->format('d/m/Y') }}</td>
                </tr>
            @elseif($receipt->mode == 'neft')
                <tr>
                    <th>Transaction ID:</th>
                    <td>{{ $receipt->transaction_id }}</td>
                </tr>
                <tr>
                    <th>Transaction Date:</th>
                    <td>{{ \Carbon\Carbon::parse($receipt->transaction_date)->format('d/m/Y') }}</td>
                </tr>
            @endif
            <tr>
                <th>Comments:</th>
                <td>{{ $receipt->comments }}</td>
            </tr>
            <tr>
                <th>Received By:</th>
                <td>{{ $receipt->collected_by }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        THIS RECEIPT IS COMPUTER GENERATED AND DOES NOT REQUIRE SIGNATURE
    </div>
</body>
</html>