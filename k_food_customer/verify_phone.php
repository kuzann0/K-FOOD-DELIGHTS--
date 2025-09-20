<?php
session_start();
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phone Verification - K-Food Delights</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .verification-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .verification-step {
            margin-bottom: 2rem;
        }

        .verification-step h3 {
            margin-bottom: 1rem;
            color: #333;
        }

        .phone-input {
            display: flex;
            gap: 0.5rem;
        }

        .phone-prefix {
            background: #f5f5f5;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            color: #666;
        }

        .otp-inputs {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin: 1rem 0;
        }

        .otp-input {
            width: 40px;
            height: 40px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1.2rem;
        }

        .timer {
            text-align: center;
            color: #666;
            margin: 1rem 0;
        }

        .resend-btn {
            background: none;
            border: none;
            color: #4c51bf;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .resend-btn:disabled {
            color: #999;
            cursor: not-allowed;
        }

        #verifyBtn {
            width: 100%;
            padding: 0.75rem;
            background: #4c51bf;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        #verifyBtn:hover {
            background: #3c4199;
        }

        .alert {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }

        .alert-error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }

        .loading {
            opacity: 0.7;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <?php include 'includes/nav.php'; ?>

    <div class="verification-container">
        <div id="step1" class="verification-step">
            <h3>Phone Verification</h3>
            <p>Enter your phone number to receive a verification code.</p>
            
            <div class="phone-input">
                <span class="phone-prefix">+63</span>
                <input type="tel" id="phoneNumber" class="form-control" placeholder="9XX XXX XXXX" maxlength="10">
            </div>
            <button id="sendOtpBtn" class="btn btn-primary mt-3" onclick="sendOTP()">Send Code</button>
        </div>

        <div id="step2" class="verification-step" style="display: none;">
            <h3>Enter Verification Code</h3>
            <p>Enter the 6-digit code sent to your phone.</p>
            
            <div class="otp-inputs">
                <input type="text" maxlength="1" class="otp-input" data-index="1">
                <input type="text" maxlength="1" class="otp-input" data-index="2">
                <input type="text" maxlength="1" class="otp-input" data-index="3">
                <input type="text" maxlength="1" class="otp-input" data-index="4">
                <input type="text" maxlength="1" class="otp-input" data-index="5">
                <input type="text" maxlength="1" class="otp-input" data-index="6">
            </div>

            <div class="timer">
                Resend code in <span id="countdown">05:00</span>
            </div>

            <button id="resendBtn" class="resend-btn" disabled onclick="sendOTP()">
                Resend Code
            </button>

            <button id="verifyBtn" onclick="verifyOTP()">
                Verify Code
            </button>
        </div>
    </div>

    <script>
        let phoneNumber = '';
        let countdownInterval;

        function startCountdown() {
            let duration = 5 * 60; // 5 minutes
            const countdownEl = document.getElementById('countdown');
            const resendBtn = document.getElementById('resendBtn');
            
            clearInterval(countdownInterval);
            resendBtn.disabled = true;
            
            countdownInterval = setInterval(() => {
                const minutes = Math.floor(duration / 60);
                const seconds = duration % 60;
                
                countdownEl.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                
                if (--duration < 0) {
                    clearInterval(countdownInterval);
                    resendBtn.disabled = false;
                    countdownEl.textContent = '00:00';
                }
            }, 1000);
        }

        function sendOTP() {
            const phoneInput = document.getElementById('phoneNumber');
            phoneNumber = phoneInput.value;
            
            if (!phoneNumber) {
                showAlert('Please enter a valid phone number', 'error');
                return;
            }

            // Show loading state
            document.querySelector('.verification-container').classList.add('loading');

            fetch('api/send_otp.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    phone_number: phoneNumber
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('step1').style.display = 'none';
                    document.getElementById('step2').style.display = 'block';
                    startCountdown();
                    showAlert('OTP sent successfully!', 'success');
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                showAlert('Failed to send OTP. Please try again.', 'error');
            })
            .finally(() => {
                document.querySelector('.verification-container').classList.remove('loading');
            });
        }

        function verifyOTP() {
            const inputs = document.querySelectorAll('.otp-input');
            const otp = Array.from(inputs).map(input => input.value).join('');
            
            if (otp.length !== 6) {
                showAlert('Please enter the complete 6-digit code', 'error');
                return;
            }

            // Show loading state
            document.querySelector('.verification-container').classList.add('loading');

            fetch('api/verify_otp.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    phone_number: phoneNumber,
                    otp: otp
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Phone number verified successfully!', 'success');
                    // Redirect after successful verification
                    setTimeout(() => {
                        window.location.href = 'profile.php';
                    }, 2000);
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                showAlert('Failed to verify OTP. Please try again.', 'error');
            })
            .finally(() => {
                document.querySelector('.verification-container').classList.remove('loading');
            });
        }

        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.textContent = message;
            
            const container = document.querySelector('.verification-container');
            container.insertBefore(alertDiv, container.firstChild);
            
            setTimeout(() => alertDiv.remove(), 5000);
        }

        // OTP input handling
        document.querySelectorAll('.otp-input').forEach(input => {
            input.addEventListener('keyup', function(e) {
                if (e.key === 'Backspace' || e.key === 'Delete') {
                    if (!this.value && this.previousElementSibling) {
                        this.previousElementSibling.focus();
                    }
                } else if (this.value) {
                    if (this.nextElementSibling) {
                        this.nextElementSibling.focus();
                    }
                }
            });

            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const paste = (e.clipboardData || window.clipboardData).getData('text');
                const inputs = document.querySelectorAll('.otp-input');
                
                for (let i = 0; i < Math.min(paste.length, inputs.length); i++) {
                    inputs[i].value = paste[i];
                }
            });
        });
    </script>
</body>
</html>
