
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



## Basic COA
### Assets
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

### Liabilities
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


### Equity
- Owner's Equity or Stockholders' Equity
- Retained Earnings
- Common Stock
- Additional Paid-in Capital


### Revenue
- Operating Revenue
  - Sales Revenue
  - Service Revenue
- Non-operating Revenue
  - Interest Income
  - Dividend Income
  - Gain on Sale of Assets
- Contra Revenue
  - Sales Returns and Allowances

### Expenses
- Cost of Goods Sold (COGS)
- Operating Expenses
  - Selling Expenses
  - General and Administrative Expenses
- Non-operating Expenses
  - Interest Expense
  - Loss on Sale of Assets
- Depreciation and Amortization
- Tax Expense
