<!DOCTYPE html>
<html>
<head>
    <title>Receipt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 0;
        }
        .header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .header img {
            width: 60px; /* Adjust size for the logo */
            margin-right: 10px;
        }
        .header-title {
            font-size: 16px;
            font-weight: bold;
        }
        .header-subtitle {
            font-size: 10px;
            margin-top: -5px;
        }
        .header-ac {
            font-size: 12px;
            margin-top: -5px;
        }
        .content table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }
        .content th, .content td {
            text-align: left;
            padding: 5px;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            font-size: 10px;
            text-align: center;
            border-top: 1px solid #000;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ $logo_image }}" alt="Logo">
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