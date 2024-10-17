<?php

namespace RingleSoft\LaravelAccounting\Services;

class PostingService
{
    /**
     * ### 1. Sales Transaction (Credit Sale)
     * Journal Entry:
     * - Debit: Accounts Receivable
     * - Credit: Sales Revenue
     * - Credit: Sales Tax Payable (if applicable)
     */
    public function saleTransaction()
    {
    }

    /**
     * ### 2. Cash Sale
     * Journal Entry:
     * - Debit: Cash
     * - Credit: Sales Revenue
     * - Credit: Sales Tax Payable (if applicable)
     */
    public function cashSale()
    {
    }

    /**
     * ### 3. Receiving Payment on Account
     * Journal Entry:
     * - Debit: Cash
     * - Credit: Accounts Receivable
     */

    public function receivingPayment(){}


    /**
     * ### 4. Purchase on Account
     * Journal Entry:
     * - Debit: Inventory (or Expense)
     * - Debit: Input VAT (if applicable)
     * - Credit: Accounts Payable
     */
    public function purchaseOnAccount()
    {
    }

    /**
     * ### 5. Cash Purchase
     * Journal Entry:
     * - Debit: Inventory (or Expense)
     * - Debit: Input VAT (if applicable)
     * - Credit: Cash
     */
    public function cashPurchase()
    {
    }

    /**
     * ### 6. Paying a Bill
     * Journal Entry:
     * - Debit: Accounts Payable
     * - Credit: Cash
     */
    public function payingBill()
    {
    }

    /**
     * ### 7. Payroll
     * Journal Entry:
     * - Debit: Salary Expense
     * - Credit: Wages Payable
     * - Credit: Payroll Tax Payable
     */
    public function payroll()
    {
    }

    /**
     * ### 8. Paying Payroll
     * Journal Entry:
     * - Debit: Wages Payable
     * - Credit: Cash
     */
    public function payingPayroll()
    {
    }

    /**
     * ### 9. Depreciation
     * Journal Entry:
     * - Debit: Depreciation Expense
     * - Credit: Accumulated Depreciation
     */
    public function depreciation()
    {
    }

    /**
     * ### 10. Prepaid Expense Recognition
     * Journal Entry:
     * - Debit: Expense
     * - Credit: Prepaid Expense
     */
    public function prepaidExpenseRecognition()
    {
    }

    /**
     * ### 11. Accrued Revenue Recognition
     * Journal Entry:
     * - Debit: Accrued Revenue
     * - Credit: Revenue
     */
    public function accruedRevenueRecognition()
    {
    }

    /**
     * ### 12. Bank Loan Receipt
     * Journal Entry:
     * - Debit: Cash
     * - Credit: Loan Payable
     */
    public function bankLoanReceipt()
    {
    }

    /**
     * ### 13. Loan Payment
     * Journal Entry:
     * - Debit: Loan Payable
     * - Debit: Interest Expense
     * - Credit: Cash
     */
    public function loanPayment()
    {
    }

    /**
     * ### 14. Owner's Investment
     * Journal Entry:
     * - Debit: Cash
     * - Credit: Owner's Equity
     */
    public function ownerInvestment()
    {
    }

    /**
     * ### 15. Dividend Payment
     * Journal Entry:
     * - Debit: Retained Earnings
     * - Credit: Cash
     */
    public function dividendPayment()
    {
    }

    /**
     * ### 16. Sales Return
     * Journal Entry:
     * - Debit: Sales Returns and Allowances
     * - Credit: Accounts Receivable (or Cash)
     */
    public function salesReturn()
    {
    }

    /**
     * ### 17. Purchase Return
     * Journal Entry:
     * - Debit: Accounts Payable (or Cash)
     * - Credit: Inventory (or Expense)
     */
    public function purchaseReturn()
    {
    }

    /**
     * ### 18. Bad Debt Write-off
     * Journal Entry:
     * - Debit: Bad Debt Expense
     * - Credit: Allowance for Doubtful Accounts
     */
    public function badDebtWriteoff()
    {
    }

    /**
     * ### 19. Inventory Count Adjustment
     * Journal Entry:
     * - Debit/Credit: Inventory
     * - Credit/Debit: Inventory Shrinkage Expense
     */
    public function inventoryCountAdjustment()
    {
    }

    /**
     * ### 20. Fixed Asset Purchase
     * Journal Entry:
     * - Debit: Fixed Asset
     * - Credit: Cash (or Accounts Payable)
     */
    public function fixedAssetPurchase()
    {
    }
}
