<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>River of God Church</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #FF6B35;
            --primary-light: #FF8E53;
            --primary-dark: #E55A2B;
            --primary-ultralight: #FFF3EC;
            --secondary: #004E89;
            --light: #FFF8F0;
            --dark: #2D2D2D;
            --gray: #6c757d;
            --gray-light: #E8E8E8;
            --success: #28a745;
            --border-radius: 12px;
            --box-shadow: 0 8px 25px rgba(255, 107, 53, 0.15);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #FFF8F0 0%, #FFE8D6 100%);
            color: var(--dark);
            line-height: 1.6;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .alert {
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background-color: #e6fffa;
            color: #00b894;
            border-left: 4px solid #00b894;
        }

        .alert-error {
            background-color: #ffe6e6;
            color: #d63031;
            border-left: 4px solid #d63031;
        }

        /* Login/Signup Specific Styles */
        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo {
            font-size: 3.5rem;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .church-name {
            font-size: 2.5rem;
            color: var(--dark);
            margin-bottom: 10px;
            font-weight: 700;
        }

        .tagline {
            font-size: 1.2rem;
            color: var(--gray);
            max-width: 500px;
            margin: 0 auto;
        }

        .auth-container {
            display: flex;
            width: 100%;
            max-width: 900px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            min-height: 550px;
            margin: 0 auto;
        }

        .welcome-section {
            flex: 1;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .welcome-section::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.1)"/></svg>');
            background-size: cover;
        }

        .welcome-content {
            position: relative;
            z-index: 1;
        }

        .welcome-title {
            font-size: 2.2rem;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .welcome-text {
            font-size: 1.1rem;
            margin-bottom: 30px;
            opacity: 0.9;
            line-height: 1.6;
        }

        .features {
            list-style: none;
            margin-bottom: 30px;
        }

        .features li {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .features i {
            margin-right: 12px;
            font-size: 1.2rem;
        }

        .form-section {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-container {
            width: 100%;
        }

        .form-title {
            font-size: 1.8rem;
            color: var(--primary);
            margin-bottom: 10px;
            font-weight: 700;
        }

        .form-subtitle {
            color: var(--gray);
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
            font-size: 0.95rem;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="tel"] {
            width: 100%;
            padding: 16px 18px;
            border: 2px solid var(--primary-ultralight);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
            background-color: white;
        }

        input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 4px rgba(255, 107, 53, 0.2);
            background-color: var(--primary-ultralight);
        }

        .password-container {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray);
            cursor: pointer;
            font-size: 1.1rem;
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .remember-me {
            display: flex;
            align-items: center;
        }

        .remember-me input {
            margin-right: 8px;
            accent-color: var(--primary);
        }

        .forgot-password {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .forgot-password:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 16px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(255, 107, 53, 0.4);
        }

        .switch-form {
            text-align: center;
            margin-top: 20px;
            color: var(--gray);
        }

        .switch-form a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            margin-left: 5px;
            transition: var(--transition);
        }

        .switch-form a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .auth-container {
                flex-direction: column;
            }

            .welcome-section {
                padding: 30px 25px;
            }

            .form-section {
                padding: 30px 25px;
            }

            .church-name {
                font-size: 2rem;
            }

            .logo {
                font-size: 3rem;
            }

            .welcome-title {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 480px) {
            .form-options {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .church-name {
                font-size: 1.8rem;
            }

            .logo {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>