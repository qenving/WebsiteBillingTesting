# Website Billing Platform

Website Billing Platform is a Laravel-based billing portal that provisions virtual servers, issues invoices, and integrates with multiple local payment gateways. The project now includes a guided first-time installation wizard, recurring billing automation, safer gateway handling, and customer self-service actions for managed services.

## Key Features

- **Guided installation experience** – Complete migrations, create the initial administrator, and seed company branding from the `/install` wizard with environment validation.
- **Recurring billing engine** – Automatically generates renewal invoices ahead of the service due date and suspends overdue services after the configured grace period.
- **Config-aware payment gateways** – Only display and enable gateways that have valid credentials to prevent failed checkout attempts.
- **Account credit wallet & ledger** – Track credit adjustments, apply balances to invoices with audit trails, and expose payment history with outstanding amounts to customers.
- **Support ticket desk** – Clients can submit and reply to tickets, while admins manage queues, internal notes, and quick status actions from the dashboard.
- **Security & operations dashboard** – Monitor revenue trends, pending invoices, login activity, open tickets, and renewal pipelines from the built-in admin overview.
- **Admin client workspace** – Review client services, recent invoices, and credit history with one-click credit adjustments.
- **Customer service controls** – Authenticated clients can reboot, power cycle, reset passwords, or open the remote console for their virtual machines.

## Getting Started

1. Ensure you are running **PHP 8.3** or newer and the required PHP extensions (OpenSSL, PDO, Mbstring, JSON, cURL) are enabled.
2. Install dependencies:
   ```bash
   composer install
   npm install && npm run dev
   ```
3. Configure your `.env` database connection and optional integrations (payment gateways, Virtfusion API keys, mail, etc.).
4. Serve the application and open the installation wizard:
   ```bash
   php artisan serve
   ```
   Visit `http://localhost:8000/install` to run migrations, create the first administrator, and store company branding details. The wizard will mark `APP_INSTALLED=true` automatically.

## Scheduled Tasks

The recurring billing automation is executed by the `billing:renewals` Artisan command. It is registered in the scheduler to run hourly, but you can adjust the frequency in `app/Console/Kernel.php`.

## Testing

Run the automated test suite with:
```bash
php artisan test
```

## License

This project is open-sourced under the [MIT License](./LICENSE).
