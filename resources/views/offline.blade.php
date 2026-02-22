<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Offline - {{ config('app.name') }}</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            text-align: center;
            max-width: 500px;
        }
        
        .icon {
            width: 120px;
            height: 120px;
            margin: 0 auto 30px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
        }
        
        h1 {
            font-size: 32px;
            margin-bottom: 15px;
            font-weight: 700;
        }
        
        p {
            font-size: 18px;
            line-height: 1.6;
            opacity: 0.9;
            margin-bottom: 30px;
        }
        
        .btn {
            display: inline-block;
            padding: 15px 40px;
            background: #fff;
            color: #667eea;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 16px;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        }
        
        .features {
            margin-top: 50px;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .features h2 {
            font-size: 20px;
            margin-bottom: 20px;
            opacity: 0.9;
        }
        
        .features ul {
            list-style: none;
            text-align: left;
            display: inline-block;
        }
        
        .features li {
            padding: 8px 0;
            opacity: 0.8;
        }
        
        .features li:before {
            content: "✓ ";
            margin-right: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">📡</div>
        
        <h1>You're Offline</h1>
        
        <p>
            It looks like you've lost your internet connection. Don't worry, 
            you can still browse recently viewed jobs and applications.
        </p>
        
        <a href="/" class="btn" onclick="window.location.reload(); return false;">
            Try Again
        </a>
        
        <div class="features">
            <h2>Available Offline:</h2>
            <ul>
                <li>Recently viewed jobs</li>
                <li>Your applications</li>
                <li>Profile information</li>
                <li>Saved searches</li>
            </ul>
        </div>
    </div>
    
    <script>
        // Auto-reload when back online
        window.addEventListener('online', () => {
            window.location.reload();
        });
        
        // Check connection every 5 seconds
        setInterval(() => {
            if (navigator.onLine) {
                window.location.reload();
            }
        }, 5000);
    </script>
</body>
</html>
