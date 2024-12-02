<!DOCTYPE html>
<html>
<head>
    <title>Receipt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header img {
            max-width: 100%;
        }
        .header h1 {
            font-size: 16px;
            margin: 10px 0;
        }
        .header p {
            font-size: 12px;
            line-height: 1.5;
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
            margin-top: 20px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ $header_image }}" alt="Header">
        <h1>DAWOODI BOHRA JAMAAT TRUST (KOLKATA)</h1>
        <p>
            Registered with the Additional Registrar of Assurances - III, as Deed No IV-00429 of 2011 (Kolkata)<br>
            A/c. FAIZ UL MAWAID IL BURHANIYAH
        </p>
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
                <th>Amount:</th>
                <td>{{ $receipt->amount }} ({{ $amount_in_words }} Only)</td>
            </tr>
            <tr>
                <th>Payment Mode:</th>
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
        </table>
    </div>

    <div class="footer">
        <p>Received By: {{ $receipt->collected_by }}</p>
    </div>
</body>
</html>