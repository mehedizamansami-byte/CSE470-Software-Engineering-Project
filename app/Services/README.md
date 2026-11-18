Reusable business logic called by Controllers. Views never talk to the database; Controllers use Models and Services.

- `Ai/AiService.php`: single entry point for AI features. Calls the FastAPI AI service (`AI_DRIVER=http`) or the offline `Ai/LocalAiEngine.php`.
- `CreditService.php`, `SubscriptionService.php`: AI credits and plans (Feature 5).
- `PaymentService.php`: simulated bKash, Nagad and card checkout (Feature 6).
- `OtpService.php`, `JwtService.php`, `EmailService.php`: OTP codes, JWT tokens and email (Feature 1).
- `AuditLogger.php`: workspace audit log (Feature 4).
- `PdfService.php`: invoices and style guide PDFs with dompdf (Features 6 and 9).
- `PostReminderService.php`: post reminders (Feature 10).
