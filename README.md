
## Accounting Basics
#### Account Classes
- Bank
- Cash
- Receivable
- Payable
- Inventory
- Tax
- Sales
- Purchase
- Cost of Goods Sold (COGS)
- Other

#### Account Types
- Asset
- Liability
- Equity
- Revenue
- Expenses

#### Account Attributes
- Name
- Account Type

#### Key accounting features to consider:
- Chart of accounts
- General ledger
- Accounts payable/receivable
- Financial statements (balance sheet, income statement, cash flow)
- Journal entries
- Bank reconciliation
- Tax management

***

### Entities and Tables

#### Chart of Accounts
- **Table**: accounts
- **Fields**: id, code, name, type, parent_id, description, is_active


#### General Ledger
- **Table**: ledger_entries
- **Fields**: id, account_id, date, description, debit_amount, credit_amount, balance


#### Journal Entries
- **Table**: journal_entries
- **Fields**: id, date, reference_no, description, is_posted
- **Table**: journal_entry_items
- **Fields**: id, journal_entry_id, account_id, description, debit_amount, credit_amount


#### Accounts Receivable
- **Table**: customers
- **Fields**: id, name, contact_info, credit_limit, balance
- **Table**: invoices
- **Fields**: id, customer_id, date, due_date, total_amount, status


#### Accounts Payable
- **Table**: vendors
- **Fields**: id, name, contact_info, balance
- **Table**: bills
- **Fields**: id, vendor_id, date, due_date, total_amount, status


#### Transactions
- **Table**: transactions
- **Fields**: id, date, type, amount, description, status


#### Fiscal Periods
- **Table**: fiscal_periods
- **Fields**: id, start_date, end_date, is_closed


#### Taxes
- **Table**: tax_rates
- **Fields**: id, name, rate, is_compound


#### Currencies
- **Table**: currencies
- **Fields**: id, code, name, symbol, exchange_rate


#### Financial Reports
- **Table**: financial_reports
- **Fields**: id, type, date, data (JSON)



### Basic COA
#### Assets
- Current Assets
  - Cash and Cash Equivalents
  - Accounts Receivable
  - Inventory
  - Prepaid Expenses
  - Short-term Investments

- Fixed Assets
  - Property, Plant, and Equipment
  - Accumulated Depreciation (contra asset)

- Other Assets
  - Long-term Investments
  - Intangible Assets (e.g., Goodwill, Patents)

- Contra Assets
  - Allowance for Doubtful Accounts

#### Liabilities
- Current Liabilities
  - Accounts Payable
  - Short-term Loans
  - Current Portion of Long-term Debt
  - Accrued Expenses
  - Sales Tax Payable
  - Income Tax Payable
  
- Long-term Liabilities
  - Long-term Loans
  - Bonds Payable
  - Deferred Tax Liabilities


#### Equity
- Owner's Equity or Stockholders' Equity
- Retained Earnings
- Common Stock
- Additional Paid-in Capital


#### Revenue
- Operating Revenue
  - Sales Revenue
  - Service Revenue
- Non-operating Revenue
  - Interest Income
  - Dividend Income
  - Gain on Sale of Assets
- Contra Revenue
  - Sales Returns and Allowances

#### Expenses
- Cost of Goods Sold (COGS)
- Operating Expenses
  - Selling Expenses
  - General and Administrative Expenses
- Non-operating Expenses
  - Interest Expense
  - Loss on Sale of Assets
- Depreciation and Amortization
- Tax Expense




## Common Accounting Postings and Ledger Entries

### 1. Sales Transaction (Credit Sale)
Journal Entry:
- Debit: Accounts Receivable
- Credit: Sales Revenue
- Credit: Sales Tax Payable (if applicable)

Ledger Entries:
- Increase Accounts Receivable
- Increase Sales Revenue
- Increase Sales Tax Payable

### 2. Cash Sale
Journal Entry:
- Debit: Cash
- Credit: Sales Revenue
- Credit: Sales Tax Payable (if applicable)

Ledger Entries:
- Increase Cash
- Increase Sales Revenue
- Increase Sales Tax Payable

### 3. Receiving Payment on Account
Journal Entry:
- Debit: Cash
- Credit: Accounts Receivable

Ledger Entries:
- Increase Cash
- Decrease Accounts Receivable

### 4. Purchase on Account
Journal Entry:
- Debit: Inventory (or Expense)
- Debit: Input VAT (if applicable)
- Credit: Accounts Payable

Ledger Entries:
- Increase Inventory (or Expense)
- Increase Input VAT
- Increase Accounts Payable

### 5. Cash Purchase
Journal Entry:
- Debit: Inventory (or Expense)
- Debit: Input VAT (if applicable)
- Credit: Cash

Ledger Entries:
- Increase Inventory (or Expense)
- Increase Input VAT
- Decrease Cash

### 6. Paying a Bill
Journal Entry:
- Debit: Accounts Payable
- Credit: Cash

Ledger Entries:
- Decrease Accounts Payable
- Decrease Cash

### 7. Payroll
Journal Entry:
- Debit: Salary Expense
- Credit: Wages Payable
- Credit: Payroll Tax Payable

Ledger Entries:
- Increase Salary Expense
- Increase Wages Payable
- Increase Payroll Tax Payable

### 8. Paying Payroll
Journal Entry:
- Debit: Wages Payable
- Credit: Cash

Ledger Entries:
- Decrease Wages Payable
- Decrease Cash

### 9. Depreciation
Journal Entry:
- Debit: Depreciation Expense
- Credit: Accumulated Depreciation

Ledger Entries:
- Increase Depreciation Expense
- Increase Accumulated Depreciation

### 10. Prepaid Expense Recognition
Journal Entry:
- Debit: Expense
- Credit: Prepaid Expense

Ledger Entries:
- Increase Expense
- Decrease Prepaid Expense

### 11. Accrued Revenue Recognition
Journal Entry:
- Debit: Accrued Revenue
- Credit: Revenue

Ledger Entries:
- Increase Accrued Revenue
- Increase Revenue

### 12. Bank Loan Receipt
Journal Entry:
- Debit: Cash
- Credit: Loan Payable

Ledger Entries:
- Increase Cash
- Increase Loan Payable

### 13. Loan Payment
Journal Entry:
- Debit: Loan Payable
- Debit: Interest Expense
- Credit: Cash

Ledger Entries:
- Decrease Loan Payable
- Increase Interest Expense
- Decrease Cash

### 14. Owner's Investment
Journal Entry:
- Debit: Cash
- Credit: Owner's Equity

Ledger Entries:
- Increase Cash
- Increase Owner's Equity

### 15. Dividend Payment
Journal Entry:
- Debit: Retained Earnings
- Credit: Cash

Ledger Entries:
- Decrease Retained Earnings
- Decrease Cash

### 16. Sales Return
Journal Entry:
- Debit: Sales Returns and Allowances
- Credit: Accounts Receivable (or Cash)

Ledger Entries:
- Increase Sales Returns and Allowances
- Decrease Accounts Receivable (or Cash)

### 17. Purchase Return
Journal Entry:
- Debit: Accounts Payable (or Cash)
- Credit: Inventory (or Expense)

Ledger Entries:
- Decrease Accounts Payable (or Cash)
- Decrease Inventory (or Expense)

### 18. Bad Debt Write-off
Journal Entry:
- Debit: Bad Debt Expense
- Credit: Allowance for Doubtful Accounts

Ledger Entries:
- Increase Bad Debt Expense
- Increase Allowance for Doubtful Accounts

### 19. Inventory Count Adjustment
Journal Entry:
- Debit/Credit: Inventory
- Credit/Debit: Inventory Shrinkage Expense

Ledger Entries:
- Increase/Decrease Inventory
- Increase/Decrease Inventory Shrinkage Expense

### 20. Fixed Asset Purchase
Journal Entry:
- Debit: Fixed Asset
- Credit: Cash (or Accounts Payable)

Ledger Entries:
- Increase Fixed Asset
- Decrease Cash (or Increase Accounts Payable)
