<?php
require_once __DIR__ . '/../config/config.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$contract = DB::fetch("SELECT c.*, 
    cust.full_name as customer_name, cust.father_husband_name, cust.urdu_name as customer_urdu, cust.cnic as customer_cnic, cust.phone as customer_phone, cust.current_address as customer_address, cust.customer_code, cust.occupation,
    b.name as branch_name, b.phone as branch_phone, b.address as branch_address
    FROM installment_contracts c 
    JOIN customers cust ON c.customer_id = cust.id 
    LEFT JOIN branches b ON c.branch_id = b.id 
    WHERE c.id = ?", [$id]);

if (!$contract) {
    die("Contract not found.");
}

$items = DB::fetchAll("SELECT ci.*, p.name as product_name, p.product_code, p.model 
    FROM contract_items ci 
    JOIN products p ON ci.product_id = p.id 
    WHERE ci.contract_id = ?", [$id]);

$schedules = DB::fetchAll("SELECT * FROM installment_schedules WHERE contract_id = ? ORDER BY installment_number ASC", [$id]);
$guarantors = DB::fetchAll("SELECT * FROM customer_guarantors WHERE customer_id = ? ORDER BY id ASC", [$contract['customer_id']]);
$g1 = $guarantors[0] ?? null;
?>
<!DOCTYPE html>
<html lang="ur" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>اقساط اقرار نامہ - <?= htmlspecialchars($contract['contract_number']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;600;700&family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Noto Nastaliq Urdu', Tahoma, Arial, sans-serif;
            background: #ffffff;
            color: #000;
            padding: 20px;
            font-size: 13px;
            line-height: 2.3;
        }
        .contract-box {
            max-width: 850px;
            margin: 0 auto;
            border: 2px solid #222;
            padding: 25px;
        }
        .header-title {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
            line-height: 2.2;
        }
        .info-table td {
            padding: 6px 8px;
            vertical-align: middle;
            line-height: 2.2;
        }
        .terms-box {
            background: #fdfdfd;
            border: 1px solid #ccc;
            padding: 12px;
            margin-top: 15px;
            line-height: 2.2;
            font-size: 12px;
        }
        @media print {
            body { padding: 0; }
            .contract-box { border: none; width: 100%; max-width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="text-center mb-3 no-print">
        <button onclick="window.print()" class="btn btn-primary px-4 py-2 fw-bold">پرنٹ کریں (Print Agreement)</button>
        <button onclick="window.close()" class="btn btn-outline-secondary px-4 py-2">بند کریں (Close)</button>
    </div>

    <div class="contract-box">
        <div class="header-title">
            <h2 class="fw-bold mb-1"><?= htmlspecialchars(get_setting('company_name_urdu', 'المدینہ انسٹالمنٹ سینٹر')) ?></h2>
            <div class="small"><?= htmlspecialchars(get_setting('company_address')) ?> &bull; فون: <?= htmlspecialchars(get_setting('company_phone')) ?></div>
            <h4 class="fw-bold mt-2 text-decoration-underline">معاہدہ بیع بالتقسیط (اقساط فارم و اقرار نامہ)</h4>
            <div class="d-flex justify-content-between px-3 mt-2 fw-bold" style="font-family:'Plus Jakarta Sans', sans-serif;" dir="ltr">
                <span>Contract #: <strong><?= htmlspecialchars($contract['contract_number']) ?></strong></span>
                <span>Date: <strong><?= format_date($contract['sale_date']) ?></strong></span>
            </div>
        </div>

        <!-- 1. Customer Details -->
        <h6 class="fw-bold text-primary border-bottom pb-1 mb-2">1۔ کوائف خریدار (Customer Details)</h6>
        <table class="table table-bordered info-table mb-3">
            <tr>
                <td style="width:18%;"><strong>نام خریدار:</strong></td>
                <td style="width:32%;"><?= htmlspecialchars($contract['customer_name']) ?> (<?= htmlspecialchars($contract['customer_urdu'] ?: '-') ?>)</td>
                <td style="width:18%;"><strong>ولدیت / شوہر:</strong></td>
                <td style="width:32%;"><?= htmlspecialchars($contract['father_husband_name'] ?: '-') ?></td>
            </tr>
            <tr>
                <td><strong>شناختی کارڈ نمبر:</strong></td>
                <td dir="ltr" class="text-end"><strong><?= htmlspecialchars($contract['customer_cnic']) ?></strong></td>
                <td><strong>موبائل نمبر:</strong></td>
                <td dir="ltr" class="text-end"><?= htmlspecialchars($contract['customer_phone']) ?></td>
            </tr>
            <tr>
                <td><strong>موجودہ رہائشی پتہ:</strong></td>
                <td colspan="3"><?= htmlspecialchars($contract['customer_address']) ?></td>
            </tr>
            <tr>
                <td><strong>پیشہ / کاروبار:</strong></td>
                <td><?= htmlspecialchars($contract['occupation'] ?: 'ملازمت / کاروبار') ?></td>
                <td><strong>کسٹمر کوڈ:</strong></td>
                <td><?= htmlspecialchars($contract['customer_code']) ?></td>
            </tr>
        </table>

        <!-- 2. Guarantor Details -->
        <h6 class="fw-bold text-primary border-bottom pb-1 mb-2">2۔ کوائف ضامن (Guarantor Details)</h6>
        <table class="table table-bordered info-table mb-3">
            <tr>
                <td style="width:18%;"><strong>نام ضامن:</strong></td>
                <td style="width:32%;"><?= htmlspecialchars($g1['full_name'] ?? 'ریکارڈ کے مطابق') ?></td>
                <td style="width:18%;"><strong>رشتہ مع خریدار:</strong></td>
                <td style="width:32%;"><?= htmlspecialchars($g1['relationship'] ?? 'رشتہ دار') ?></td>
            </tr>
            <tr>
                <td><strong>ضامن شناختی کارڈ:</strong></td>
                <td dir="ltr" class="text-end"><?= htmlspecialchars($g1['cnic'] ?? '-') ?></td>
                <td><strong>ضامن موبائل فون:</strong></td>
                <td dir="ltr" class="text-end"><?= htmlspecialchars($g1['phone'] ?? '-') ?></td>
            </tr>
            <tr>
                <td><strong>ضامن کا مکمل پتہ:</strong></td>
                <td colspan="3"><?= htmlspecialchars($g1['address'] ?? '-') ?></td>
            </tr>
        </table>

        <!-- 3. Purchased Items -->
        <h6 class="fw-bold text-primary border-bottom pb-1 mb-2">3۔ تفصیل اشیاء / پراڈکٹس (Product Details)</h6>
        <table class="table table-bordered info-table mb-3 text-center">
            <thead class="table-light">
                <tr>
                    <th>نام سامان / پراڈکٹ</th>
                    <th>ماڈل نمبر</th>
                    <th>سیریل نمبر / IMEI</th>
                    <th>تعداد</th>
                    <th>کل اقساط قیمت</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                    <td><?= htmlspecialchars($item['model'] ?: '-') ?></td>
                    <td dir="ltr"><?= htmlspecialchars($item['serial_number'] ?: 'N/A') ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td><?= format_currency($item['installment_price']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- 4. Financial Plan & Payment Terms -->
        <h6 class="fw-bold text-primary border-bottom pb-1 mb-2">4۔ مالیاتی شیڈول اور اقساط کا پلان</h6>
        <table class="table table-bordered info-table mb-3 text-center">
            <tr>
                <td><strong>کل اقساط قیمت:</strong><br><?= format_currency($contract['installment_price_total']) ?></td>
                <td><strong>ایڈوانس وصولی:</strong><br><?= format_currency($contract['down_payment']) ?></td>
                <td><strong>باقی قسط رقم:</strong><br><span class="text-danger fw-bold"><?= format_currency($contract['financed_amount']) ?></span></td>
                <td><strong>ماہانہ قسط رقم:</strong><br><span class="text-success fw-bold"><?= format_currency($contract['installment_amount']) ?></span></td>
                <td><strong>کل اقساط مدت:</strong><br><?= $contract['installment_count'] ?> ماہ</td>
            </tr>
        </table>

        <!-- 5. Legal Terms -->
        <div class="terms-box">
            <strong>شرائط و ضوابط (Terms & Conditions):</strong>
            <ol class="mb-0 pe-3">
                <li>خریدار اقرار کرتا ہے کہ اس نے مذکورہ بالا سامان بالکل درست و تسلی بخش حالت میں وصول کر لیا ہے۔</li>
                <li>خریدار ہر ماہ کی مقررہ تاریخ (Due Date) تک اپنی ماہانہ قسط دکان پر جمع کروانے کا پابند ہوگا۔</li>
                <li>آخری قسط اور تمام واجبات کی مکمل ادائیگی تک سامان کی ملکیت ادارہ ہذا کے پاس محفوظ رہے گی اور خریدار سامان کو بیچنے یا رہن رکھنے کا مجاز نہیں ہوگا۔</li>
                <li>قسط میں تاخیر کی صورت میں مروجہ قواعد کے مطابق جرمانہ لاگو ہوگا۔ اگر خریدار مسلسل اقساط میں نادہند رہے تو ادارہ بغیر نوٹس سامان واپس لینے اور قانونی کارروائی کا حق محفوظ رکھتا ہے۔</li>
                <li>ضامن اقرار کرتا ہے کہ خریدار کی عدم ادائیگی کی صورت میں وہ تمام باقی رقم اپنی ذاتی حیثیت سے ادا کرنے کا مکمل پابند ہوگا۔</li>
            </ol>
        </div>

        <!-- Signatures -->
        <div class="row g-4 text-center mt-4 pt-3">
            <div class="col-3">
                <div class="border-top border-dark pt-2">
                    <strong>دستخط خریدار</strong><br>
                    <small>Customer Signature</small>
                </div>
            </div>
            <div class="col-3">
                <div class="border-top border-dark pt-2">
                    <strong>دستخط ضامن</strong><br>
                    <small>Guarantor Signature</small>
                </div>
            </div>
            <div class="col-3">
                <div class="border-top border-dark pt-2">
                    <strong>گواہ شد</strong><br>
                    <small>Witness</small>
                </div>
            </div>
            <div class="col-3">
                <div class="border-top border-dark pt-2">
                    <strong>مہر و دستخط ادارہ</strong><br>
                    <small>Authorized Signature</small>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
