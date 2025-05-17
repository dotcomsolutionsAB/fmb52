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
            padding: 0;
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
            font-size: 40px;
            font-weight: bold;
            color: #a52a2a;
        }
        .header-subtitle {
            font-size: 30px;
            color: #a52a2a;
        }
        .header-ac {
            font-size: 30px;
            color: #a52a2a;
        }
		
		.receipt-table {
			width: 90%;
			margin: 2mm 10mm 0 10mm;
			border-collapse: collapse;
			font-family: Arial, sans-serif;
			font-size: 32px;
			color: #a52a2a;
		}

		.receipt-table td {
			padding: 10px 10px;
			vertical-align: middle;
		}

		.receipt-table .label {
			font-weight: bold;
			width: 18%;
		}

		.receipt-table .value {
			font-weight: bold;
			color: #000;
			width: 44%;
		}
		
		.receipt-table tr {
			padding : 1px;	
		}

		.receipt-table tr td:nth-child(3) {
			font-weight: bold;
			width: 18%;
		}

		.receipt-table tr td:nth-child(4) {
			font-weight: bold;
			color: #000;
			width: 20%;
		}

    </style>
</head>
<body>
	<br/><br/><br/><br/><br/><br/><br/><br/>
    <div class="header" style="text-align: center">
        <div>
            <div class="header-title">DAWOODI BOHRA JAMAAT TRUST (KOLKATA)</div>
            <div class="header-subtitle">
                Registered with the Additional Registrar of Assurances - III, as Deed No IV-00429 of 2011 (Kolkata)
            </div>
            <div class="header-ac">A/c. FAIZ UL MAWAID IL BURHANIYAH</div>
        </div>
    </div>

    <table class="receipt-table">
		<tbody>
			<tr>
				<td class="label">Name :</td>
				<td class="value">{{ $receipt->name }}</td>
				<td class="label">Receipt No :</td>
				<td class="value">{{ $receipt->receipt_no }}</td>
			</tr>
			<tr>
				<td class="label">ITS No :</td>
				<td class="value">{{ $receipt->its }}</td>
				<td class="label">Date :</td>
				<td class="value">{{ \Carbon\Carbon::parse($receipt->date)->format('d-m-Y') }}</td>
			</tr>
			<tr>
				<td class="label">Sector :</td>
				<td class="value">{{ $receipt->sector->name }}</td>
				<td class="label">Sub Sector :</td>
				<td class="value">{{ $receipt->sub_sector ?? '-' }}</td>
			</tr>
			<tr>
				<td class="label">Folio :</td>
				<td class="value">{{ $receipt->folio_no }}</td>
				<td class="label">Year :</td>
				<td class="value">{{ $receipt->year }}</td>
			</tr>
			<tr>
				<td class="label">Mode :</td>
				<td class="value">{{ ucfirst($receipt->mode) }}</td>
				<td></td>
				<td></td>
			</tr>
			<tr>
				<td class="label">Amount :</td>
				<td class="value">Rs. {{ number_format($receipt->amount, 2) }} ({{ $amount_in_words }} Only)</td>
				<td></td>
				<td></td>
			</tr>
			<tr>
				<td class="label">Received By :</td>
				<td class="value">{{ $receipt->collected_by }}</td>
				<td></td>
				<td></td>
			</tr>
			<tr>
				<td class="label">Comments :</td>
				<td class="value">{{ $receipt->comments }}</td>
				<td></td>
				<td></td>
			</tr>
		</tbody>
	</table>



</body>
</html>