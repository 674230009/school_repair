# 🛠️ ระบบแจ้งซ่อมอุปกรณ์และสถานที่ภายในโรงเรียน (School Repair System)

ระบบบริหารจัดการและแจ้งซ่อมอุปกรณ์ เครื่องใช้ สิ่งอำนวยความสะดวก และสถานที่ภายในโรงเรียน พัฒนาด้วย **PHP Native (PDO)** และ **MySQL** พร้อมหน้าจอผู้ใช้งานที่รองรับการทำงาน Responsive บนทุกอุปกรณ์ด้วย **Bootstrap 5**

---

## 🌟 ฟีเจอร์หลักของระบบ (Features)

- 🔐 **ระบบจัดการผู้ใช้งานและสิทธิ์เข้าถึง (Multi-Role Authentication)**
  - **ผู้ดูแลระบบ (Admin):** มองเห็นภาพรวมงานทั้งหมด, มอบหมายงานให้ช่างซ่อม, กำหนดวันเสร็จ (Due Date), อัปเดตสถานะงาน
  - **ครู / บุคลากร (Teacher):** กรอกแบบฟอร์มแจ้งซ่อมใหม่, แนบรูปภาพปัญหา, ติดตามสถานะงานของตนเอง
  - **ช่างซ่อม / เจ้าหน้าที่พัสดุ (Technician):** ดูรายการงานที่ได้รับมอบหมาย, อัปเดตสถานะการซ่อม (รับเรื่อง/กำลังดำเนินการ/เสร็จสิ้น), บันทึกผลการซ่อมและค่าใช้จ่าย, แนบรูปภาพหลังซ่อม (After)
- 📝 **ระบบลงทะเบียนสมาชิก (Register) & เข้าสู่ระบบ (Login)**
  - สมัครสมาชิกระบุบทบาทได้ทันที
  - ระบบรักษาความปลอดภัยรหัสผ่านด้วย `password_hash()` (BCrypt)
- 📊 **แผงควบคุมสถิติ (Dashboard)**
  - แสดงจำนวนงานทั้งหมด, งานรอดำเนินการ, งานกำลังดำเนินการ และงานที่เสร็จสมบูรณ์
- 💬 **ระบบความคิดเห็นและการสื่อสาร (Comments & Discussion)**
  - ครู ช่างซ่อม และ Admin สามารถพิมพ์ข้อความโต้ตอบกันในแต่ละใบแจ้งซ่อมได้
- 📷 **ระบบแนบรูปภาพประกอบ (Image Uploads)**
  - รองรับการแนบรูปภาพก่อนซ่อม (Before) และรูปภาพหลังซ่อมเสร็จ (After)

---

## 💻 เทคโนโลยีที่ใช้ (Tech Stack)

- **Backend:** PHP (PDO Extension, Class Database Singleton)
- **Database:** MySQL / MariaDB (utf8mb4)
- **Frontend:** HTML5, CSS3, JavaScript (ES6)
- **UI Framework:** Bootstrap 5.3, FontAwesome 6.4
- **Alert System:** SweetAlert2

---

## 📁 โครงสร้างโฟลเดอร์ (Directory Structure)

```text
school_repair/
├── assets/
│   └── css/
│       └── style.css           # ไฟล์ปรับแต่งสไตล์ CSS
├── config/
│   ├── auth.php                # ตรวจสอบ Session & สิทธิ์ผู้ใช้งาน (Auth & Roles)
│   └── database.php            # คลาสเชื่อมต่อฐานข้อมูล PDO Singleton
├── includes/
│   ├── header.php              # Navbar & Header template
│   └── footer.php              # Footer template
├── uploads/                    # โฟลเดอร์เก็บรูปภาพประกอบที่อัปโหลด
├── create_request.php          # หน้าแบบฟอร์มแจ้งซ่อมใหม่
├── index.php                   # หน้าหลัก Dashboard แสดงรายการแจ้งซ่อม
├── login.php                   # หน้าเข้าสู่ระบบ
├── register.php                # หน้าสมัครสมาชิกใหม่
├── view_request.php            # หน้าดูรายละเอียดใบแจ้งซ่อม, อัปเดตสถานะ & ความคิดเห็น
├── school_repair.sql           # ไฟล์โครงสร้างฐานข้อมูลและข้อมูลเริ่มต้น (Seed Data)
└── README.md                   # เอกสารอธิบายโครงการ
```

---

## 🚀 ขั้นตอนการติดตั้งและใช้งาน (Installation Guide)

### 1. การจัดเตรียมไฟล์โครงการ
นำโฟลเดอร์โครงการไปวางไว้ในโฟลเดอร์ `htdocs` ของ XAMPP:
```text
C:\xampp\htdocs\school_repair
```

### 2. เปิดใช้งาน XAMPP
เปิดโปรแกรม **XAMPP Control Panel** และกด Start ในบริการ:
- **Apache**
- **MySQL**

### 3. นำเข้าฐานข้อมูล (Import Database)
1. เปิด Web Browser ไปที่ [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/)
2. สร้างฐานข้อมูลใหม่ชื่อ `school_repair` (Collation: `utf8mb4_unicode_ci`)
3. กดเลือกฐานข้อมูล `school_repair` -> ไปที่แท็บ **Import** (นำเข้า)
4. เลือกไฟล์ `C:\xampp\htdocs\school_repair\school_repair.sql` แล้วกด **Go** (หรือ Execute นำเข้าข้อมูล)

*(หรือนำเข้าผ่าน Command Line)*
```bash
mysql -u root < C:\xampp\htdocs\school_repair\school_repair.sql
```

### 4. เข้าใช้งานระบบ
เปิดเบราว์เซอร์แล้วเข้าที่ URL:
👉 **[http://localhost/school_repair](http://localhost/school_repair)**

---

## 🗄️ โครงสร้างฐานข้อมูล (Database Schema)

1. **`users`**: ตารางผู้ใช้งาน (`id`, `username`, `password`, `fullname`, `role`, `email`, `created_at`)
2. **`repair_requests`**: ตารางใบแจ้งซ่อม (`id`, `request_no`, `user_id`, `building`, `room`, `problem_type`, `description`, `urgency`, `status`, `assigned_to`, `due_date`, `completion_date`, `cost`, `result`, `created_at`)
3. **`repair_images`**: ตารางรูปภาพ (`id`, `repair_id`, `image_type`, `image_path`, `uploaded_at`)
4. **`comments`**: ตารางความคิดเห็น (`id`, `repair_id`, `user_id`, `comment`, `created_at`)
