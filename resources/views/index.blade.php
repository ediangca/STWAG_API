<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STWAG API</title>
    <link rel="icon" type="image/png" href="{{ asset('img/stwaglogo.png') }}">
    <!-- Optional: simple styling -->
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            color: #333;
        }
        .container {
            text-align: center;
            background: #fff;
            padding: 2rem 3rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        img.logo {
            width: 120px;
            margin-bottom: 1rem;
        }
        h1 {
            margin: 0.5rem 0;
        }
        p {
            margin: 0.25rem 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="{{ asset('img/stwaglogo.png') }}" alt="STWAG Logo" class="logo">
        <h1>STWAG API</h1>
        <p>Version: 1.0.0</p>
        <p>Laravel Version: {{ app()->version() }}</p>
        <p>Timezone: {{ config('app.timezone') }}</p>
        <p>Timestamp: {{ now()->toDateTimeString() }}</p>
        <p>This API is under development. Please check back soon.</p>
        <hr style="margin: 1rem 0;">
        <p>Developed by: Mr. Ebrahim Diangca and John Louis Mercaral, MIS</p>
    </div>
</body>
</html>
