# Installment Pro — Complete Installment & Financing Management Software
### قسطوں پر خرید و فروخت اور اقساط کی وصولی کا مکمل سافٹ ویئر

An enterprise-grade, high-performance web software designed for installment shops, electronics stores, mobile phone dealerships, motorcycle showrooms, furniture stores, solar businesses, and credit financing companies.

Built with **Core PHP, MySQL (PDO), HTML5, CSS3, JavaScript, Bootstrap 5, FontAwesome 6, and Chart.js** without any third-party framework dependencies.

---

## 🚀 Default Credentials & Access
- **URL**: `http://localhost/Installment/`
- **Default Super Admin**:
  - **Username**: `admin`
  - **Password**: `admin123`
- **Cashier Account**:
  - **Username**: `aliraza`
  - **Password**: `admin123`

---

## 🌟 Key Features & Complete Modules

### 1. Dashboard & Business Overview (ڈیش بورڈ)
- Distinction between **Total Sales Revenue**, **Physical Cash Inflow**, **Market Receivables**, and **Net Profit**.
- Real-time KPIs: Active plans, overdue installments, today's collections, monthly collections, gross sales markup, expenses, and net profit.
- Interactive Chart.js visualizations: Monthly Sales vs Collections trend and payment collection channels.

### 2. Customer Management (کسٹمر مینجمنٹ)
- 360-degree Customer Profile with photo, CNIC front/back records, credit limits, occupation, and internal remarks.
- Dual Guarantors recording (نام، شناختی کارڈ، رشتہ، پتہ، موبائل فون).
- Duplicate CNIC and duplicate phone warning checks.
- Risk categorization: New, Regular, Premium, High-Risk, Defaulter, Blacklisted.
- Complete Customer Financial Ledger statement with print and Excel exports.

### 3. Product & Inventory Management (مصنوعات اور اسٹاک)
- Product catalog supporting Electronics, Mobile Phones (with IMEI tracking), Motorcycles (Engine/Chassis), Solar Systems, Appliances, Furniture.
- Dual Pricing Model: **Purchase Cost**, **Cash Sale Price**, and **Installment Sale Price**.
- Minimum stock level alerts and stock valuation reports.

### 4. Installment Plan & Contract Management (اقساط کا پلان)
- Unique Contract Numbering (`INS-2026-XXXX`).
- Live schedule calculator: Financed amount, down payment deduction, duration (months), and monthly installment calculations.
- Automatic generation of individual schedule rows per installment.
- Full Urdu Legal Agreement (بیع بالتقسیط اقرار نامہ) with witness and guarantor terms.

### 5. Smart Payment Collection Management (اقساط کی وصولی)
- Fast counter payment collection with **Oldest-Due-First Smart Allocation**.
- Supported payment methods: Cash in Hand, Bank Transfer, JazzCash, Easypaisa, Cheque, Bank Deposit.
- Automatic Receipt generation (`RCP-2026-XXXXX`).
- 80mm POS Thermal Slip printing and standard A4 receipt printing.
- Public QR code verification link on every receipt.
- One-click WhatsApp message receipt dispatcher.
- Secure payment reversal module with mandatory audit reason logging.

### 6. Defaulter & Recovery Management (تاخیر اور ڈیفالٹرز)
- Overdue Aging Categorization: **1–7 Days**, **8–30 Days**, **31–60 Days**, **61–90 Days**, and **90+ Days (Severe)**.
- Promise-to-Pay (PTP) records and field recovery visit notes.
- Instant pre-filled Urdu WhatsApp overdue reminder dispatching.

### 7. Cashbook & Operating Expenses (کیش بک اور مالیاتی نظام)
- Daily physical cash-in-hand control and bank balances.
- Categorized expense management: Rent, Salaries, Electricity, Internet, Recovery Fuel, Maintenance, Tea/Office.
- Automatic inflow / outflow entries upon collections, down payments, refunds, and expense payments.

### 8. Profit & Loss Statement (نفع و نقصان کی رپورٹ)
- Accurate accounting calculation: Gross Markup Revenue minus Product Purchase Cost (COGS) minus Operating Expenses = **Net Operating Profit**.

### 9. Point of Sale (POS) Counter Billing
- Quick counter interface for counter operators to process immediate cash sales or start new installment agreements.

### 10. Multi-Branch Management (متعدد برانچز)
- Branch isolation, branch switcher, and consolidated oversight.

### 11. Security, Backup & Audit Trail
- Passwords hashed with bcrypt (`password_hash`).
- Complete Audit Log tracking user logins, contract creations, payment collections, reversals, and IP addresses.
- One-click `.SQL` database backup download and recovery.

### 12. Bilingual Urdu & English (RTL & LTR)
- Dynamic switch between English (LTR) and Urdu (اردو RTL) with authentic typography and RTL layout stylesheets.
