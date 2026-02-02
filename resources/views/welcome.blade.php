<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>NEMS API | National Education Management System</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
        <style>
            :root {
                --primary: #2563eb;
                --primary-hover: #1d4ed8;
                --bg-gradient-start: #f8fafc;
                --bg-gradient-end: #e2e8f0;
                --text-main: #1e293b;
                --text-muted: #64748b;
            }

            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
            }

            body {
                font-family: 'Inter', sans-serif;
                background: linear-gradient(135deg, var(--bg-gradient-start), var(--bg-gradient-end));
                color: var(--text-main);
                height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
            }

            .container {
                text-align: center;
                max-width: 600px;
                padding: 3rem;
                background: rgba(255, 255, 255, 0.8);
                backdrop-filter: blur(12px);
                border-radius: 24px;
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.1);
                border: 1px solid rgba(255, 255, 255, 0.5);
                animation: fadeIn 0.8s ease-out;
            }

            h1 {
                font-size: 2.5rem;
                font-weight: 700;
                letter-spacing: -0.025em;
                margin-bottom: 1rem;
                background: linear-gradient(135deg, #1e293b, #334155);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }

            p {
                font-size: 1.125rem;
                line-height: 1.6;
                color: var(--text-muted);
                margin-bottom: 2.5rem;
                font-weight: 300;
            }

            .btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 0.875rem 2rem;
                font-size: 1rem;
                font-weight: 600;
                color: white;
                background-color: var(--primary);
                text-decoration: none;
                border-radius: 9999px;
                transition: all 0.2s ease;
                box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2), 0 2px 4px -1px rgba(37, 99, 235, 0.1);
            }

            .btn:hover {
                background-color: var(--primary-hover);
                transform: translateY(-2px);
                box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3), 0 4px 6px -2px rgba(37, 99, 235, 0.15);
            }

            .footer-text {
                margin-top: 2rem;
                font-size: 0.875rem;
                color: #94a3b8;
            }

            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>NEMS API</h1>
            <p>
                Welcome to the National Education Management System API layer.<br>
                Secure, standard-compliant, and reliable data access for education services.
            </p>
            <a href="/docs/api" class="btn">
                Read Documentation &rarr;
            </a>
            <div class="footer-text">
                &copy; {{ date('Y') }} NEMS. All rights reserved.
            </div>
        </div>
    </body>
</html>
