# CAALAMI ELECTRONICS ERP (Full-Stack PHP + MySQL on XAMPP)

Nidaamka Maamulka Ganacsiga ee **CAALAMI ELECTRONICS** oo si buuxda loogu dhisay **Native PHP 8.3 & MySQL**, looguna talagalay inuu toos uga shaqeeyo XAMPP Apache adigoon u baahnayn Node.js ama React compilation.

---

## 🚀 Sida Loogu Furo XAMPP (Quick Start)

1. Fur **XAMPP Control Panel**, bilow **Apache** iyo **MySQL**.
2. Hubi in faylashu ku jiraan: `C:\xampp\htdocs\caalami-erp\`
3. Fur browser-kaaga:
   👉 **[http://localhost/caalami-erp/](http://localhost/caalami-erp/)**
4. Database-ka `caalami_erp` iyo dhammaan tables-ka iyo xogta bilowga ah (seed data) waxay u abuurmayaan **si toos ah (automatically)** marka aad markii ugu horeysay bogga furto!

---

## 📁 Qaab-dhismeedka Mashruuca (Project Structure)

- `index.php` — Bogga guud ee Dashboard-ka (KPI Summary Cards, Red Overdue Alerts, Invoices Table & Debt Collections).
- `create_sales.php` — POS Terminal / Create Sales oo leh:
  - **Editable Sale Date**: Taariikhda iibka oo gacanta laga beddeli karo iyo badhanka degdegga ah ee "Today".
  - Katalog-ka badeecadaha (Mobiles, Laptops, Accessories, Networking, iwm).
  - Qiimaha Retail iyo Wholesale oo la kala dooran karo.
  - Cart interactive ah oo wax lagu dari karo laguna xisaabin karo qiimaha.
  - Payment Gateways: Cash, ZAAD (Telesom), e-Dahab (Somtel), EVC Plus, Bank Transfer, Customer Debt.
  - Save & Print Receipt (Daabacaadda rasiidka rasmiga ah).
- `invoices.php` — Liiska dhammaan qaansheekooyinka (Invoices), shaandhaynta deynta (Due Debt, Overdue, Paid).
- `sales_items.php` — Faahfaahinta qalabka la kala iibiyay iyo xaddigooda.
- `quotations.php` — Samaynta Quotations cusub iyo u beddelista Invoice dhab ah (Convert to Invoice).
- `exchanges.php` — Diiwaangelinta beddelaada qalabka (Product Exchanges & Warranty Returns).
- `inventory.php` — Maamulka kaydka bakhaarka (Stock Levels) iyo digniinta alaabta sii dhammaanaysa (Low Stock Alert).
- `customers.php` — Liiska macaamiisha, telefoonadooda, iyo xisaabaadka deynta.
- `deliveries.php` — Dabagalka gaadiidka alaabta lagu geeyo macaamiisha (Delivery Fleet Tracker).
- `api/process_sale.php` — API-ga diiwaangeliya iibka cusub ee POS-ka una dira MySQL.
- `api/process_payment.php` — API-ga qaabila bixinta deynta (Settlement) ee macaamiisha.
- `config/db.php` — Xidhiidhka MySQL (PDO) iyo abuurista tables-ka tooska ah.
- `includes/header.php`, `includes/sidebar.php`, `includes/footer.php` — Qaybaha guud ee design-ka iyo modal-ada.

---

## 🎨 Naqshadda (Design & Styling)

- **Tailwind CSS**: Waxaa lagu shubay CDN rasmi ah si design-ku u ahaado mid aad u casri ah, nadiif ah, oo mobile-friendly ah.
- **Lucide Icons**: Astaamo casri ah oo SVG ah.
- **Color Palette**:
  - Primary Navy: `#0B2A5B`
  - Crimson Accent: `#c00000` / `#e00000`
  - Status Indicators: Emerald (Paid), Amber (Partial / Warning), Crimson Red (Overdue Debt).
