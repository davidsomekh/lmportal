<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SolidCAM License Assistant</title>
    <link rel="icon" href="favicon.ico">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 24px;
            color: #2b171b;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at 20% 20%, rgba(215, 25, 32, 0.14), transparent 35%),
                radial-gradient(circle at 85% 80%, rgba(255, 205, 209, 0.5), transparent 30%),
                #fff9fa;
        }

        .chat {
            width: min(100%, 460px);
            overflow: hidden;
            border: 1px solid #ecdde0;
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.94);
            box-shadow: 0 24px 70px rgba(89, 23, 31, 0.14);
            backdrop-filter: blur(16px);
        }

        .chat-header {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
            padding: 18px 20px;
            border-bottom: 1px solid #f0e3e5;
        }

        .logo {
            width: min(100%, 260px);
            height: auto;
            display: block;
        }

        .status-line {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #806c70;
            font-size: 13px;
        }

        .online {
            color: #09856f;
        }

        .messages {
            min-height: 275px;
            padding: 28px 20px;
        }

        .bubble {
            width: fit-content;
            max-width: 85%;
            padding: 13px 16px;
            border-radius: 6px 18px 18px 18px;
            line-height: 1.5;
            background: #f7eaec;
            box-shadow: 0 8px 22px rgba(89, 23, 31, 0.08);
            animation: bubble-in 0.45s ease both;
        }

        .question {
            display: none;
        }

        .typing {
            display: flex;
            gap: 5px;
            align-items: center;
            min-height: 46px;
        }

        .typing span {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #a77f87;
            animation: bounce 1.1s infinite ease-in-out;
        }

        .typing span:nth-child(2) {
            animation-delay: 0.15s;
        }

        .typing span:nth-child(3) {
            animation-delay: 0.3s;
        }

        .answer {
            display: none;
            margin: 18px 0 0 auto;
            border-radius: 18px 6px 18px 18px;
            color: white;
            background: linear-gradient(135deg, #e12830, #a70f18);
        }

        .answer span {
            display: block;
        }

        .error-message {
            display: none;
            margin-top: 14px;
        }

        .activation-link {
            display: none;
            width: fit-content;
            margin-top: 12px;
            padding: 9px 12px;
            border-radius: 9px;
            color: white;
            font-size: 13px;
            font-weight: 750;
            text-decoration: none;
            background: linear-gradient(135deg, #e12830, #a70f18);
        }

        .activation-link:hover {
            filter: brightness(1.08);
        }

        .composer {
            display: flex;
            gap: 10px;
            padding: 16px;
            border-top: 1px solid #f0e3e5;
            background: #fff7f8;
        }

        input {
            min-width: 0;
            flex: 1;
            padding: 13px 15px;
            border: 1px solid #dfcdd1;
            border-radius: 13px;
            outline: none;
            color: #2b171b;
            font: inherit;
            background: white;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input:focus {
            border-color: #d71920;
            box-shadow: 0 0 0 3px rgba(215, 25, 32, 0.18);
        }

        button {
            padding: 0 18px;
            border: 0;
            border-radius: 13px;
            color: white;
            font: 700 14px inherit;
            cursor: pointer;
            background: linear-gradient(135deg, #e12830, #a70f18);
            transition: transform 0.2s, filter 0.2s;
        }

        button:hover {
            filter: brightness(1.08);
            transform: translateY(-1px);
        }

        button:disabled,
        input:disabled {
            cursor: not-allowed;
            opacity: 0.6;
        }

        @keyframes bounce {
            0%, 60%, 100% { transform: translateY(0); opacity: 0.55; }
            30% { transform: translateY(-5px); opacity: 1; }
        }

        @keyframes bubble-in {
            from { opacity: 0; transform: translateY(10px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
            }
        }
    </style>
</head>
<body>
    <main class="chat" aria-label="SolidCAM assistant">
        <header class="chat-header">
            <img
                class="logo"
                src="SolidCAM-AI-Logo-w-subline.svg"
                alt="SolidCAM AI"
            >
            <span class="status-line"><span class="online" aria-hidden="true">●</span> Online</span>
        </header>

        <section class="messages" aria-live="polite">
            <div class="bubble typing" id="typing" aria-label="Assistant is typing">
                <span></span><span></span><span></span>
            </div>
            <div class="bubble question" id="question">
                Hi! What is your SolidCAM product key?
            </div>
            <div class="bubble answer" id="answer">
                <span id="answerText"></span>
            </div>
            <div class="bubble error-message" id="errorMessage">
                <span>License not found. If you’re using an older license, you can verify it here:</span>
                <a
                    class="activation-link"
                    id="activationLink"
                    href="http://activations.solidcam.com/ems/customerLogin.html"
                    target="_blank"
                    rel="noopener noreferrer"
                >Check on SolidCAM</a>
            </div>
        </section>

        <form class="composer" id="keyForm" action="details.php" method="get" autocomplete="off">
            <input
                id="productKey"
                name="key"
                type="text"
                placeholder="Enter your product key"
                aria-label="SolidCAM product key"
                disabled
                required
            >
            <button type="submit" disabled>Send</button>
        </form>
    </main>

    <script>
        const typing = document.getElementById("typing");
        const question = document.getElementById("question");
        const form = document.getElementById("keyForm");
        const input = document.getElementById("productKey");
        const button = form.querySelector("button");
        const answer = document.getElementById("answer");
        const answerText = document.getElementById("answerText");
        const errorMessage = document.getElementById("errorMessage");
        const activationLink = document.getElementById("activationLink");
        const parameters = new URLSearchParams(window.location.search);
        const licenseNotFound = parameters.get("error") === "license_not_found";
        const failedKey = parameters.get("key") ?? "";

        function showPrompt() {
            typing.style.display = "none";
            question.style.display = "block";
            input.disabled = false;
            button.disabled = false;
            input.focus();
        }

        if (licenseNotFound) {
            showPrompt();
            answerText.textContent = failedKey;
            answer.style.display = "block";
            errorMessage.style.display = "block";
            activationLink.style.display = "inline-flex";
            input.value = "";
            window.history.replaceState({}, document.title, window.location.pathname);
        } else {
            window.setTimeout(showPrompt, 1400);
        }

        window.addEventListener("pageshow", (event) => {
            if (!event.persisted && !input.readOnly) return;

            answerText.textContent = "";
            answer.style.display = "none";
            errorMessage.style.display = "none";
            activationLink.style.display = "none";
            input.readOnly = false;
            input.disabled = false;
            button.disabled = false;
            input.focus();
        });

        form.addEventListener("submit", (event) => {
            event.preventDefault();

            const key = input.value.trim();
            if (!key) return;

            answerText.textContent = "Checking your product key…";
            answer.style.display = "block";
            errorMessage.style.display = "none";
            activationLink.style.display = "none";
            input.readOnly = true;
            button.disabled = true;

            window.setTimeout(() => form.submit(), 700);
        });
    </script>
</body>
</html>