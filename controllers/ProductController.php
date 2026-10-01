<?php

class ProductController
{
    private PDO $db;

    public function __construct(PDO $db) { $this->db = $db; }

    public function index(): void
    {
        $search = trim((string) ($_GET['search'] ?? ''));
        $categoryId = filter_var($_GET['category_id'] ?? null, FILTER_VALIDATE_INT);
        $sql = 'SELECT p.i_ProductID, p.c_ProductName, p.i_SupplierID, p.i_CategoryID, p.c_Unit, p.i_Price,
                       c.c_CategoryName, s.c_SupplierName
                FROM tb_products p
                LEFT JOIN tb_categories c ON c.i_CategoryID = p.i_CategoryID
                LEFT JOIN tb_suppliers s ON s.i_SupplierID = p.i_SupplierID';
        $where = [];
        $params = [];
        if ($search !== '') {
            $where[] = '(p.c_ProductName LIKE :search OR CAST(p.i_ProductID AS CHAR) LIKE :id_search OR p.c_Unit LIKE :unit_search)';
            $params['search'] = '%' . $search . '%';
            $params['id_search'] = '%' . $search . '%';
            $params['unit_search'] = '%' . $search . '%';
        }
        if ($categoryId !== false && $categoryId !== null) {
            $where[] = 'p.i_CategoryID = :category_id';
            $params['category_id'] = $categoryId;
        }
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY p.i_ProductID DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        Response::success($stmt->fetchAll(), 'โหลดรายการสินค้าสำเร็จ');
    }

    public function show(string $id): void
    {
        if (!$this->validId($id)) Response::error('รหัสสินค้าไม่ถูกต้อง', 422);
        $stmt = $this->db->prepare('SELECT p.*, c.c_CategoryName, s.c_SupplierName FROM tb_products p LEFT JOIN tb_categories c ON c.i_CategoryID = p.i_CategoryID LEFT JOIN tb_suppliers s ON s.i_SupplierID = p.i_SupplierID WHERE p.i_ProductID = ?');
        $stmt->execute([(int) $id]);
        $product = $stmt->fetch();
        if (!$product) Response::error('ไม่พบสินค้าที่ต้องการ', 404);
        Response::success($product, 'โหลดข้อมูลสินค้าสำเร็จ');
    }

    public function store(): void
    {
        $data = $this->validatedInput();
        if (isset($data['errors'])) Response::error('กรุณาตรวจสอบข้อมูลที่กรอก', 422, $data['errors']);
        $stmt = $this->db->prepare('INSERT INTO tb_products (c_ProductName, i_SupplierID, i_CategoryID, c_Unit, i_Price) VALUES (:name, :supplier, :category, :unit, :price)');
        $stmt->execute(['name' => $data['name'], 'supplier' => $data['supplier'], 'category' => $data['category'], 'unit' => $data['unit'], 'price' => $data['price']]);
        $this->showCreated((int) $this->db->lastInsertId());
    }

    public function update(string $id): void
    {
        if (!$this->validId($id)) Response::error('รหัสสินค้าไม่ถูกต้อง', 422);
        $data = $this->validatedInput();
        if (isset($data['errors'])) Response::error('กรุณาตรวจสอบข้อมูลที่กรอก', 422, $data['errors']);
        $stmt = $this->db->prepare('UPDATE tb_products SET c_ProductName = :name, i_SupplierID = :supplier, i_CategoryID = :category, c_Unit = :unit, i_Price = :price WHERE i_ProductID = :id');
        $stmt->execute(['name' => $data['name'], 'supplier' => $data['supplier'], 'category' => $data['category'], 'unit' => $data['unit'], 'price' => $data['price'], 'id' => (int) $id]);
        if ($stmt->rowCount() === 0 && !$this->exists((int) $id)) Response::error('ไม่พบสินค้าที่ต้องการแก้ไข', 404);
        $this->showCreated((int) $id, 'แก้ไขข้อมูลสินค้าสำเร็จ', 200);
    }

    public function destroy(string $id): void
    {
        if (!$this->validId($id)) Response::error('รหัสสินค้าไม่ถูกต้อง', 422);
        if (!$this->exists((int) $id)) Response::error('ไม่พบสินค้าที่ต้องการลบ', 404);
        $check = $this->db->prepare('SELECT COUNT(*) FROM tb_orderdetails WHERE i_ProductID = ?');
        $check->execute([(int) $id]);
        if ((int) $check->fetchColumn() > 0) Response::error('สินค้านี้มีประวัติในรายการสั่งซื้อ จึงไม่สามารถลบได้', 409);
        $stmt = $this->db->prepare('DELETE FROM tb_products WHERE i_ProductID = ?');
        $stmt->execute([(int) $id]);
        Response::success(['i_ProductID' => (int) $id], 'ลบสินค้าเรียบร้อยแล้ว');
    }

    public function categories(): void
    {
        $stmt = $this->db->query('SELECT i_CategoryID, c_CategoryName FROM tb_categories ORDER BY c_CategoryName');
        Response::success($stmt->fetchAll(), 'โหลดหมวดหมู่สำเร็จ');
    }

    public function suppliers(): void
    {
        $stmt = $this->db->query('SELECT i_SupplierID, c_SupplierName FROM tb_suppliers ORDER BY c_SupplierName');
        Response::success($stmt->fetchAll(), 'โหลดผู้จำหน่ายสำเร็จ');
    }

    private function validatedInput(): array
    {
        $body = json_decode(file_get_contents('php://input'), true);
        if (!is_array($body)) $body = $_POST;
        $name = trim((string) ($body['c_ProductName'] ?? ''));
        $unit = trim((string) ($body['c_Unit'] ?? ''));
        $supplier = filter_var($body['i_SupplierID'] ?? null, FILTER_VALIDATE_INT);
        $category = filter_var($body['i_CategoryID'] ?? null, FILTER_VALIDATE_INT);
        $priceRaw = $body['i_Price'] ?? null;
        $errors = [];
        if ($name === '') $errors['c_ProductName'] = 'กรุณากรอกชื่อสินค้า';
        elseif (mb_strlen($name) > 30) $errors['c_ProductName'] = 'ชื่อสินค้าต้องไม่เกิน 30 ตัวอักษร';
        if ($unit === '') $errors['c_Unit'] = 'กรุณากรอกหน่วยบรรจุ';
        elseif (mb_strlen($unit) > 30) $errors['c_Unit'] = 'หน่วยบรรจุต้องไม่เกิน 30 ตัวอักษร';
        if ($supplier === false || $supplier < 1 || !$this->lookupExists('tb_suppliers', 'i_SupplierID', (int) $supplier)) $errors['i_SupplierID'] = 'กรุณาเลือกผู้จำหน่ายที่มีอยู่';
        if ($category === false || $category < 1 || !$this->lookupExists('tb_categories', 'i_CategoryID', (int) $category)) $errors['i_CategoryID'] = 'กรุณาเลือกหมวดหมู่ที่มีอยู่';
        if (!is_numeric($priceRaw) || (float) $priceRaw <= 0 || (float) $priceRaw > 99999999) $errors['i_Price'] = 'ราคาต้องมากกว่า 0 และไม่เกิน 99,999,999';
        if ($errors) return ['errors' => $errors];
        return ['name' => $name, 'unit' => $unit, 'supplier' => (int) $supplier, 'category' => (int) $category, 'price' => (float) $priceRaw];
    }

    private function lookupExists(string $table, string $column, int $id): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM {$table} WHERE {$column} = ? LIMIT 1");
        $stmt->execute([$id]);
        return (bool) $stmt->fetchColumn();
    }

    private function exists(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM tb_products WHERE i_ProductID = ?');
        $stmt->execute([$id]);
        return (bool) $stmt->fetchColumn();
    }

    private function validId(string $id): bool { return ctype_digit($id) && (int) $id > 0; }

    private function showCreated(int $id, string $message = 'เพิ่มสินค้าเรียบร้อยแล้ว', int $status = 201): void
    {
        $stmt = $this->db->prepare('SELECT p.i_ProductID, p.c_ProductName, p.i_SupplierID, p.i_CategoryID, p.c_Unit, p.i_Price, c.c_CategoryName, s.c_SupplierName FROM tb_products p LEFT JOIN tb_categories c ON c.i_CategoryID = p.i_CategoryID LEFT JOIN tb_suppliers s ON s.i_SupplierID = p.i_SupplierID WHERE p.i_ProductID = ?');
        $stmt->execute([$id]);
        Response::success($stmt->fetch(), $message, $status);
    }
}
