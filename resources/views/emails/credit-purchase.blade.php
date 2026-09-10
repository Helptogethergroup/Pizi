<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credit Purchase Confirmation</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #6B5B95 0%, #8B4789 100%);
            color: #ffffff;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .content {
            padding: 30px;
        }
        .greeting {
            font-size: 16px;
            color: #333;
            margin-bottom: 20px;
        }
        .greeting strong {
            color: #6B5B95;
        }
        .confirmation-box {
            background-color: #f0f4ff;
            border-left: 4px solid #6B5B95;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .confirmation-box .status {
            color: #28a745;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .details-section {
            margin: 25px 0;
        }
        .details-section h3 {
            color: #6B5B95;
            font-size: 14px;
            text-transform: uppercase;
            margin-bottom: 15px;
            border-bottom: 2px solid #6B5B95;
            padding-bottom: 10px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: #666;
            font-weight: 500;
        }
        .detail-value {
            color: #333;
            font-weight: 600;
        }
        .balance-highlight {
            background-color: #fff3cd;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            text-align: center;
        }
        .balance-highlight .current-balance {
            font-size: 24px;
            color: #856404;
            font-weight: bold;
            margin-top: 10px;
        }
        .footer {
            background-color: #f9f9f9;
            padding: 20px 30px;
            text-align: center;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #666;
        }
        .footer p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>✅ Credits Purchased Successfully</h1>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">
                Hello <strong>{{ $owner->name }}</strong>,
            </div>

            <p>Thank you for purchasing credits on Pizi.in! Your payment has been processed successfully.</p>

            <!-- Confirmation Box -->
            <div class="confirmation-box">
                <div class="status">✅ Payment Confirmed</div>
                <p style="margin: 5px 0; color: #555;">Your account has been credited with new leads.</p>
            </div>

            <!-- Transaction Details -->
            <div class="details-section">
                <h3>Transaction Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Plan Purchased</span>
                    <span class="detail-value">{{ $plan }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Amount Paid</span>
                    <span class="detail-value">₹{{ number_format($amount, 2) }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Credits Added</span>
                    <span class="detail-value" style="color: #28a745;">+{{ $credits }} Credits</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Transaction ID</span>
                    <span class="detail-value">{{ $transactionId }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date & Time</span>
                    <span class="detail-value">{{ $purchaseDate }}</span>
                </div>
            </div>

            <!-- Balance Information -->
            <div class="details-section">
                <h3>Credit Balance</h3>
                <div class="detail-row">
                    <span class="detail-label">Previous Balance</span>
                    <span class="detail-value">{{ $previousBalance }} Credits</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Credits Added</span>
                    <span class="detail-value" style="color: #28a745;">+{{ $credits }} Credits</span>
                </div>
            </div>

            <!-- Current Balance Highlight -->
            <div class="balance-highlight">
                <div style="font-size: 12px; color: #856404; text-transform: uppercase;">Your Current Balance</div>
                <div class="current-balance">{{ $currentBalance }} Credits</div>
                <p style="margin: 8px 0 0 0; color: #856404; font-size: 12px;">Ready to find properties</p>
            </div>

            <p style="color: #666; font-size: 14px; margin-top: 25px;">
                You can now use these credits to unlock leads and view property details. Login to your account and start exploring!
            </p>

            <p style="color: #666; font-size: 14px;">
                If you have any questions, contact our support team at <strong>info@pizi.in</strong>
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>© 2026 Pizi.in - PG & Hostel Management Platform</p>
            <p>This is an automated email. Please do not reply to this message.</p>
        </div>
    </div>
</body>
</html>