<?php

namespace App\Modules\Accounting\Services;

use App\Core\Services\SettingsService;
use App\Modules\Accounting\Models\ChartOfAccount;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Purchasing\Models\PurchaseReceipt;
use App\Modules\Purchasing\Models\SupplierPayment;
use App\Modules\Sales\Models\SalesDelivery;
use App\Modules\Sales\Models\SalesInvoice;
use App\Modules\Sales\Models\SalesReceipt;

class WorkflowJournalPostingService
{
    public function __construct(
        protected SettingsService $settingsService,
        protected JournalEntryService $journalEntryService,
    ) {
    }

    public function postSalesDelivery(SalesDelivery $delivery): JournalEntry
    {
        $delivery->loadMissing(['items.product']);
        $this->ensureSourceNotPosted($delivery);

        $inventoryAccountId = $this->accountIdFromSetting('inventory_account_code', '1200');
        $cogsAccountId = $this->accountIdFromSetting('cogs_account_code', '5000');

        $amount = round($delivery->items->sum(function ($item): float {
            $unitCost = $item->product ? (float) $item->product->cost_price : (float) $item->unit_price;

            return (float) $item->delivered_qty * $unitCost;
        }), 4);

        if ($amount <= 0) {
            throw new \DomainException('Sales delivery posting amount must be greater than zero.');
        }

        $entry = $this->journalEntryService->create([
            'entry_date' => $delivery->delivery_date?->toDateString() ?? now()->toDateString(),
            'reference_no' => $delivery->delivery_no,
            'source_type' => $delivery->getMorphClass(),
            'source_id' => $delivery->getKey(),
            'description_translations' => [
                'en' => 'Inventory issue from sales delivery '.$delivery->delivery_no,
                'ar' => 'إخراج مخزون من تسليم مبيعات '.$delivery->delivery_no,
            ],
        ], [
            [
                'chart_of_account_id' => $cogsAccountId,
                'debit' => $amount,
                'credit' => 0,
            ],
            [
                'chart_of_account_id' => $inventoryAccountId,
                'debit' => 0,
                'credit' => $amount,
            ],
        ]);

        return $this->journalEntryService->post($entry);
    }

    public function postPurchaseReceipt(PurchaseReceipt $receipt): JournalEntry
    {
        $receipt->loadMissing(['items']);
        $this->ensureSourceNotPosted($receipt);

        $inventoryAccountId = $this->accountIdFromSetting('inventory_account_code', '1200');
        $accountsPayableAccountId = $this->accountIdFromSetting('accounts_payable_account_code', '2000');

        $amount = round($receipt->items->sum(
            fn ($item): float => (float) $item->received_qty * (float) $item->unit_cost
        ), 4);

        if ($amount <= 0) {
            throw new \DomainException('Purchase receipt posting amount must be greater than zero.');
        }

        $entry = $this->journalEntryService->create([
            'entry_date' => $receipt->received_date?->toDateString() ?? now()->toDateString(),
            'reference_no' => $receipt->receipt_no,
            'source_type' => $receipt->getMorphClass(),
            'source_id' => $receipt->getKey(),
            'description_translations' => [
                'en' => 'Inventory receipt from purchase receipt '.$receipt->receipt_no,
                'ar' => 'إضافة مخزون من استلام مشتريات '.$receipt->receipt_no,
            ],
        ], [
            [
                'chart_of_account_id' => $inventoryAccountId,
                'debit' => $amount,
                'credit' => 0,
            ],
            [
                'chart_of_account_id' => $accountsPayableAccountId,
                'debit' => 0,
                'credit' => $amount,
            ],
        ]);

        return $this->journalEntryService->post($entry);
    }

    public function postSalesInvoice(SalesInvoice $invoice): JournalEntry
    {
        $this->ensureSourceNotPosted($invoice);

        $accountsReceivableId = $this->accountIdFromSetting('accounts_receivable_account_code', '1100');
        $salesRevenueId = $this->accountIdFromSetting('sales_revenue_account_code', '4000');
        $amount = round((float) $invoice->grand_total, 4);

        if ($amount <= 0) {
            throw new \DomainException('Sales invoice posting amount must be greater than zero.');
        }

        $entry = $this->journalEntryService->create([
            'entry_date' => $invoice->invoice_date?->toDateString() ?? now()->toDateString(),
            'reference_no' => $invoice->invoice_no,
            'source_type' => $invoice->getMorphClass(),
            'source_id' => $invoice->getKey(),
            'description_translations' => [
                'en' => 'Accounts receivable from sales invoice '.$invoice->invoice_no,
                'ar' => 'إثبات ذمم مدينة من فاتورة مبيعات '.$invoice->invoice_no,
            ],
        ], [
            [
                'chart_of_account_id' => $accountsReceivableId,
                'debit' => $amount,
                'credit' => 0,
            ],
            [
                'chart_of_account_id' => $salesRevenueId,
                'debit' => 0,
                'credit' => $amount,
            ],
        ]);

        return $this->journalEntryService->post($entry);
    }

    public function postSalesReceipt(SalesReceipt $receipt): JournalEntry
    {
        $this->ensureSourceNotPosted($receipt);

        $cashAccountId = $this->accountIdFromSetting('cash_account_code', '1000');
        $accountsReceivableId = $this->accountIdFromSetting('accounts_receivable_account_code', '1100');
        $amount = round((float) $receipt->amount, 4);

        if ($amount <= 0) {
            throw new \DomainException('Sales receipt posting amount must be greater than zero.');
        }

        $entry = $this->journalEntryService->create([
            'entry_date' => $receipt->receipt_date?->toDateString() ?? now()->toDateString(),
            'reference_no' => $receipt->receipt_no,
            'source_type' => $receipt->getMorphClass(),
            'source_id' => $receipt->getKey(),
            'description_translations' => [
                'en' => 'Cash collection for sales receipt '.$receipt->receipt_no,
                'ar' => 'تحصيل نقدي لإيصال قبض '.$receipt->receipt_no,
            ],
        ], [
            [
                'chart_of_account_id' => $cashAccountId,
                'debit' => $amount,
                'credit' => 0,
            ],
            [
                'chart_of_account_id' => $accountsReceivableId,
                'debit' => 0,
                'credit' => $amount,
            ],
        ]);

        return $this->journalEntryService->post($entry);
    }

    public function postSupplierPayment(SupplierPayment $payment): JournalEntry
    {
        $this->ensureSourceNotPosted($payment);

        $accountsPayableId = $this->accountIdFromSetting('accounts_payable_account_code', '2000');
        $cashAccountId = $this->accountIdFromSetting('cash_account_code', '1000');
        $amount = round((float) $payment->amount, 4);

        if ($amount <= 0) {
            throw new \DomainException('Supplier payment posting amount must be greater than zero.');
        }

        $entry = $this->journalEntryService->create([
            'entry_date' => $payment->payment_date?->toDateString() ?? now()->toDateString(),
            'reference_no' => $payment->payment_no,
            'source_type' => $payment->getMorphClass(),
            'source_id' => $payment->getKey(),
            'description_translations' => [
                'en' => 'Supplier payment '.$payment->payment_no,
                'ar' => 'دفعة مورد '.$payment->payment_no,
            ],
        ], [
            [
                'chart_of_account_id' => $accountsPayableId,
                'debit' => $amount,
                'credit' => 0,
            ],
            [
                'chart_of_account_id' => $cashAccountId,
                'debit' => 0,
                'credit' => $amount,
            ],
        ]);

        return $this->journalEntryService->post($entry);
    }

    protected function ensureSourceNotPosted(object $source): void
    {
        if (! method_exists($source, 'getMorphClass') || ! method_exists($source, 'getKey')) {
            throw new \InvalidArgumentException('Invalid posting source model.');
        }

        $alreadyPosted = JournalEntry::query()
            ->where('source_type', $source->getMorphClass())
            ->where('source_id', $source->getKey())
            ->exists();

        if ($alreadyPosted) {
            throw new \DomainException('A journal entry already exists for this source document.');
        }
    }

    protected function accountIdFromSetting(string $settingKey, string $defaultCode): int
    {
        $code = (string) $this->settingsService->get('accounting', $settingKey, $defaultCode);

        $account = ChartOfAccount::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->first();

        if (! $account) {
            throw new \DomainException(sprintf(
                'Accounting setting "%s" references unknown or inactive account code "%s".',
                $settingKey,
                $code
            ));
        }

        return (int) $account->id;
    }
}
