# Northwind Product Manager

เว็บแอปจัดการสินค้าโดยใช้ PHP 8, MySQL และ REST API ตามโครงสร้าง Router → Controller → Response มีหน้าเว็บภาษาไทยสำหรับค้นหา เพิ่ม แก้ไข และลบสินค้า ใช้ตาราง `tb_products`, `tb_categories`, `tb_suppliers` และ `tb_orderdetails` จากฐานข้อมูล Northwind ที่อาจารย์ให้มา

## เริ่มต้นบนเครื่อง

1. สร้างฐานข้อมูล MySQL ชื่อ `db_northwind` แล้วนำเข้าไฟล์ [`database/dbNorthwind.sql`](database/dbNorthwind.sql) (ไฟล์ SQL สร้างและเลือกฐานข้อมูลชื่อนี้ไว้แล้ว)
2. ตั้งค่า environment variables ก่อนเปิดเว็บ:

   ```text
   DB_HOST=localhost
   DB_PORT=3306
   DB_DATABASE=db_northwind
   DB_USERNAME=root
   DB_PASSWORD=root
   ```

3. เปิดโฟลเดอร์ผ่าน Apache ที่เปิด `mod_rewrite` และ `AllowOverride All` เช่น MAMP แล้วเข้า URL ของโปรเจกต์

## API

ทุก endpoint ส่ง JSON รูปแบบ `{ "success": true, "message": "...", "data": ... }` เมื่อสำเร็จ และตอบ validation errors พร้อม HTTP 422 เมื่อข้อมูลไม่ถูกต้อง

| Method | Endpoint | การทำงาน |
| --- | --- | --- |
| GET | `/api/products?search=chai&category_id=1` | ค้นหาและกรองสินค้า |
| GET | `/api/products/{id}` | ดูสินค้า |
| POST | `/api/products` | เพิ่มสินค้า |
| PUT | `/api/products/{id}` | แก้ไขสินค้า |
| DELETE | `/api/products/{id}` | ลบสินค้า (ป้องกันสินค้าที่ถูกใช้อยู่ใน `tb_orderdetails`) |
| GET | `/api/categories` | รายการหมวดหมู่สำหรับฟอร์ม |
| GET | `/api/suppliers` | รายการผู้จำหน่ายสำหรับฟอร์ม |

ตัวอย่าง body สำหรับ POST/PUT:

```json
{
  "c_ProductName": "Chai",
  "i_SupplierID": 1,
  "i_CategoryID": 1,
  "c_Unit": "10 boxes x 20 bags",
  "i_Price": 18.5
}
```

## Deploy บน Railway (PaaS)

1. สร้าง repository บน GitHub แล้ว push โปรเจกต์นี้ จาก Railway เลือก **New Project → Deploy from GitHub Repo** แล้วเลือก repository นี้ Railway จะตรวจพบ `Dockerfile` และ build PHP + Apache ให้อัตโนมัติ
2. เพิ่ม **MySQL service** ในโปรเจกต์ Railway แล้วกำหนด environment variables ใน PHP service ด้วย reference variables (เปลี่ยน `MySQL` ให้ตรงกับชื่อ service):

   ```text
   DB_HOST=${{MySQL.MYSQLHOST}}
   DB_PORT=${{MySQL.MYSQLPORT}}
   DB_DATABASE=${{MySQL.MYSQLDATABASE}}
   DB_USERNAME=${{MySQL.MYSQLUSER}}
   DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}
   ```

3. Import [`database/railway-import.sql`](database/railway-import.sql) เข้า database ที่ MySQL service สร้างไว้ (`MYSQLDATABASE`) ไฟล์นี้ไม่มีคำสั่งสร้าง/เลือกฐานข้อมูลแบบตายตัว จึงใช้กับชื่อ Railway ได้ หาก import จากเครื่องภายนอก ให้เปิด TCP Proxy ชั่วคราวในหน้า Networking ของ MySQL service หรือใช้ Railway CLI (`railway connect mysql`) จากนั้นปิด public access หลัง import เสร็จ
4. ตรวจว่าตัวแปร `DB_DATABASE` ชี้ไปยัง database ที่เพิ่ง import แล้ว กด deploy PHP service และเลือก **Generate Domain** ใน Networking เพื่อให้เว็บเข้าถึงได้จากอินเทอร์เน็ต

เก็บรหัสผ่านฐานข้อมูลไว้ใน Railway Variables เท่านั้น ไม่ commit ลง repository ฐานข้อมูล Railway เป็น private โดยปริยาย และเว็บใช้ private network เชื่อม MySQL
