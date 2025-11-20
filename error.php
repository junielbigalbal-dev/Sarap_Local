<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - Sarap Local</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            text-align: center;
        }
        .error-container {
            max-width: 600px;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: var(--primary-orange-dark);
            margin-top: 0;
        }
        .error-message {
            margin: 20px 0;
            padding: 15px;
            background-color: var(--secondary-orange);
            border: 1px solid var(--primary-orange-light);
            border-radius: 4px;
            color: var(--primary-orange-dark);
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--primary-orange);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: var(--primary-orange-dark);
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>Oops! Something went wrong</h1>
        <div class="error-message">
            We're experiencing technical difficulties. Our team has been notified and is working to fix the issue.
        </div>
        <p>Please try again later or contact support if the problem persists.</p>
        <a href="index.php" class="btn">Return to Home</a>
    </div>
</body>
</html>
