<?php
session_start();

if (!isset($_SERVER['HTTP_REFERER']) || !strpos($_SERVER['HTTP_REFERER'], 'contact.php')) {
    header('Location: index.php');
    exit();
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>42 - The Answer</title>
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="icon" href="../img/MangaMuse_White-Book.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cabin:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">
    <style>
        .easter-egg {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #fff;
            text-align: center;
            padding: 20px;
            background: #252525;
            position: relative;
            overflow: hidden;
        }

        .easter-egg::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, #000 25%, transparent 25%),
                        linear-gradient(-45deg, #000 25%, transparent 25%),
                        linear-gradient(45deg, transparent 75%, #000 75%),
                        linear-gradient(-45deg, transparent 75%, #000 75%);
            background-size: 20px 20px;
            background-position: 0 0, 0 10px, 10px -10px, -10px 0px;
            opacity: 0.1;
            animation: backgroundMove 20s linear infinite;
        }

        @keyframes backgroundMove {
            0% {
                background-position: 0 0, 0 10px, 10px -10px, -10px 0px;
            }
            100% {
                background-position: 20px 20px, 20px 30px, 30px 10px, 10px 20px;
            }
        }

        .easter-egg h1 {
            font-size: 8em;
            margin: 0;
            color: #64b5f6;
            text-shadow: 0 0 10px rgba(100, 181, 246, 0.5);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .easter-egg p {
            font-size: 1.5em;
            max-width: 600px;
            margin: 20px 0;
            line-height: 1.6;
        }

        .quote {
            font-style: italic;
            color: #aaa;
            margin: 20px 0;
            font-size: 1.2em;
        }

        .binary {
            position: absolute;
            color: #64b5f6;
            opacity: 0.1;
            font-family: monospace;
            font-size: 1.2em;
            animation: float 10s linear infinite;
            pointer-events: none;
        }

        @keyframes float {
            0% { transform: translateY(0); opacity: 0; }
            50% { opacity: 0.1; }
            100% { transform: translateY(-100vh); opacity: 0; }
        }

        .secret-button {
            background: transparent;
            border: 2px solid #64b5f6;
            color: #64b5f6;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.2em;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .secret-button:hover {
            background: #64b5f6;
            color: #252525;
            transform: scale(1.1);
        }

        @media (max-width: 768px) {
            .easter-egg h1 {
                font-size: 4em;
            }
            
            .easter-egg p {
                font-size: 1.2em;
                padding: 0 20px;
            }
        }
    </style>
</head>
<body>
    <?php include '../partials/header.php'; ?>
    <main class="easter-egg">
        <h1>42</h1>
        <p>Congratulations! You've found the answer to the Ultimate Question of Life, the Universe, and Everything!</p>
        <div class="quote">"Don't Panic!" - The Hitchhiker's Guide to the Galaxy</div>
        <p>But now that you know the answer, do you know what the question is?</p>
        <button class="secret-button" onclick="revealSecret()">Ask Deep Thought</button>
        
        <!-- Dynamic binary rain effect -->
        <script>
            function createBinary() {
                const binary = document.createElement('div');
                binary.className = 'binary';
                binary.style.left = Math.random() * 100 + 'vw';
                binary.textContent = Math.random().toString(2).substr(2, 8);
                document.querySelector('.easter-egg').appendChild(binary);
                
                setTimeout(() => {
                    binary.remove();
                }, 10000);
            }

            setInterval(createBinary, 500);

            function revealSecret() {
                const responses = [
                    "I have calculated the answer for 7.5 million years. Don't rush me.",
                    "Have you tried turning it off and on again?",
                    "Error 42: Too much wisdom to display.",
                    "The question? That's going to take another 7.5 million years...",
                    "Sorry, that's classified information.",
                    "Maybe the real question is the friends we made along the way.",
                    "Loading question... Estimated time: 7.5 million years"
                ];
                
                const randomResponse = responses[Math.floor(Math.random() * responses.length)];
                alert(randomResponse);
            }
        </script>
    </main>
    <?php include '../partials/footer.php'; ?>
</body>
</html> 