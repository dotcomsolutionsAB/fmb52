<!DOCTYPE html>
<html>
<head>
    <title>PDF Example</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
        }
        .title {
            text-align: center;
            margin-bottom: 20px;
        }
        .content {
            text-align: justify;
        }
    </style>
</head>
<body>
    <div class="title">
        <h1>{{ $title }}</h1>
        <p>Date: {{ $date }}</p>
    </div>
    <div class="content">
        <p>
            This is a sample PDF document generated using Laravel and the DomPDF package.
        </p>
    </div>
</body>
</html>