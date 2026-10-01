<!doctype html>
<html lang="th" data-bs-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#f4f6f9">
  <title>จัดการข้อมูลสินค้า | Northwind</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body class="py-5">
  <button class="theme-toggle-btn" id="themeToggle" type="button" title="สลับโหมดสว่าง/มืด" aria-label="สลับโหมดสว่าง/มืด"><i class="bi bi-moon-stars-fill text-warning fs-5" id="themeIcon"></i></button>
  <main class="container page-container">
    <div class="row justify-content-center"><div class="col-12 col-xl-11">
      <section class="card card-custom p-3 p-sm-4 p-lg-5">
        <header class="text-center mb-4">
          <div class="header-icon mb-3"><i class="bi bi-box-seam-fill fs-2"></i></div>
          <div class="eyebrow">NORTHWIND DATABASE</div>
          <h1 class="fw-bold mb-1">จัดการข้อมูลสินค้า</h1>
          <p class="text-secondary small mb-0">ค้นหา เพิ่ม แก้ไข และลบรายการสินค้า</p>
        </header>

        <div class="row g-3 mb-4 stats-grid">
          <div class="col-12 col-sm-4"><div class="stat-card"><span class="stat-icon"><i class="bi bi-boxes"></i></span><span class="stat-label">สินค้าทั้งหมด</span><strong id="totalProducts">—</strong><small>รายการในคลัง</small></div></div>
          <div class="col-12 col-sm-4"><div class="stat-card"><span class="stat-icon"><i class="bi bi-grid"></i></span><span class="stat-label">หมวดหมู่</span><strong id="totalCategories">—</strong><small>หมวดหมู่สินค้า</small></div></div>
          <div class="col-12 col-sm-4"><div class="stat-card"><span class="stat-icon"><i class="bi bi-currency-baht"></i></span><span class="stat-label">ราคาเฉลี่ย</span><strong id="averagePrice">—</strong><small>ต่อสินค้า</small></div></div>
        </div>

        <section class="inventory-section">
          <div class="section-heading d-flex align-items-center justify-content-between gap-3 mb-3">
            <div><h2 class="h5 fw-semibold mb-1">รายการสินค้า</h2><p class="text-secondary small mb-0">ข้อมูลสินค้าจากฐานข้อมูล Northwind</p></div>
            <button class="btn btn-gradient" id="addProduct" type="button"><i class="bi bi-plus-lg me-1"></i> เพิ่มสินค้า</button>
          </div>
          <div class="toolbar row g-2 align-items-center mb-3">
            <div class="col-12 col-md"><label class="input-group search-box"><span class="input-group-text"><i class="bi bi-search"></i></span><input id="searchInput" class="form-control" type="search" placeholder="ค้นหาชื่อสินค้า หรือรหัสสินค้า..." autocomplete="off"></label></div>
            <div class="col-8 col-md-3"><label class="visually-hidden" for="categoryFilter">หมวดหมู่</label><select id="categoryFilter" class="form-select"><option value="">ทุกหมวดหมู่</option></select></div>
            <div class="col-4 col-md-auto"><button class="btn btn-outline-secondary w-100" id="refreshButton" type="button" title="โหลดข้อมูลใหม่"><i class="bi bi-arrow-clockwise"></i><span class="d-none d-lg-inline ms-1">รีเฟรช</span></button></div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-2"><span class="small text-secondary">ผลการค้นหา</span><span class="small text-secondary" id="resultCount"></span></div>
          <div class="table-responsive rounded-3 inventory-table-wrap">
            <table class="table align-middle mb-0"><thead><tr><th>สินค้า</th><th>หมวดหมู่</th><th>ผู้จำหน่าย</th><th>หน่วยบรรจุ</th><th class="text-end">ราคา</th><th class="text-end">จัดการ</th></tr></thead><tbody id="productRows"><tr><td colspan="6" class="loading-cell text-center py-5"><span class="spinner-border spinner-border-sm text-primary me-2"></span>กำลังโหลดข้อมูลสินค้า...</td></tr></tbody></table>
          </div>
          <div class="d-flex justify-content-between align-items-center pt-3 small text-secondary"><span id="tableSummary">กำลังโหลดรายการ</span><span class="database-status"><i class="bi bi-circle-fill me-1"></i> Northwind · MySQL API</span></div>
        </section>
      </section>
      <p class="text-center text-secondary small mt-3">Northwind Product Manager <span class="mx-1">·</span> PHP &amp; MySQL</p>
    </div></div>
  </main>

  <div class="toast-region" id="toastRegion" aria-live="polite" aria-atomic="true"></div>
  <dialog class="product-dialog" id="productDialog">
    <form id="productForm" class="dialog-form" novalidate>
      <div class="dialog-heading"><div class="text-center w-100"><div class="header-icon header-icon-small mb-2"><i class="bi bi-box-seam-fill"></i></div><div class="eyebrow">PRODUCT DETAILS</div><h2 id="dialogTitle" class="h4 fw-bold mb-0">เพิ่มสินค้าใหม่</h2></div><button type="button" class="icon-button" id="closeDialog" aria-label="ปิด"><i class="bi bi-x-lg"></i></button></div>
      <input type="hidden" id="productId">
      <div class="mb-3 field"><label for="productName" class="form-label fw-semibold text-secondary small"><i class="bi bi-tag me-1"></i>ชื่อสินค้า <span class="text-danger">*</span></label><input id="productName" name="c_ProductName" class="form-control" maxlength="30" required placeholder="เช่น ชาเขียวสำเร็จรูป"><div class="invalid-feedback d-block field-error" data-error="c_ProductName"></div></div>
      <div class="row g-3 mb-3"><div class="col-12 col-md-6 field"><label for="productCategory" class="form-label fw-semibold text-secondary small"><i class="bi bi-grid me-1"></i>หมวดหมู่สินค้า <span class="text-danger">*</span></label><select id="productCategory" name="i_CategoryID" class="form-select" required><option value="">-- เลือกหมวดหมู่ --</option></select><div class="invalid-feedback d-block field-error" data-error="i_CategoryID"></div></div><div class="col-12 col-md-6 field"><label for="productSupplier" class="form-label fw-semibold text-secondary small"><i class="bi bi-truck me-1"></i>ผู้จัดจำหน่าย <span class="text-danger">*</span></label><select id="productSupplier" name="i_SupplierID" class="form-select" required><option value="">-- เลือกผู้จัดจำหน่าย --</option></select><div class="invalid-feedback d-block field-error" data-error="i_SupplierID"></div></div></div>
      <div class="row g-3 mb-4"><div class="col-12 col-md-6 field"><label for="productUnit" class="form-label fw-semibold text-secondary small"><i class="bi bi-box me-1"></i>หน่วยบรรจุ <span class="text-danger">*</span></label><input id="productUnit" name="c_Unit" class="form-control" maxlength="30" required placeholder="เช่น 24 - 12 oz bottles"><div class="invalid-feedback d-block field-error" data-error="c_Unit"></div></div><div class="col-12 col-md-6 field"><label for="productPrice" class="form-label fw-semibold text-secondary small"><i class="bi bi-currency-baht me-1"></i>ราคาสินค้า <span class="text-danger">*</span></label><div class="input-group"><span class="input-group-text">฿</span><input id="productPrice" name="i_Price" class="form-control" type="number" min="0.01" max="99999999" step="0.01" required placeholder="0.00"></div><div class="invalid-feedback d-block field-error" data-error="i_Price"></div></div></div>
      <div class="form-message alert alert-warning py-2 small" id="formMessage" role="alert"></div>
      <div class="d-grid gap-2"><button type="submit" class="btn btn-gradient btn-lg" id="saveButton"><i class="bi bi-check-circle-fill me-1"></i> บันทึกสินค้า</button><button type="button" class="btn btn-outline-secondary" id="cancelDialog">ยกเลิก</button></div>
    </form>
  </dialog>
  <dialog class="confirm-dialog" id="deleteDialog"><div class="confirm-icon"><i class="bi bi-exclamation-lg"></i></div><h2 class="h5 fw-bold">ลบสินค้านี้หรือไม่?</h2><p id="deleteMessage" class="text-secondary small">การลบจะไม่สามารถย้อนกลับได้</p><div class="d-flex justify-content-center gap-2 mt-4"><button class="btn btn-outline-secondary" id="cancelDelete" type="button">ยกเลิก</button><button class="btn btn-danger" id="confirmDelete" type="button"><i class="bi bi-trash me-1"></i>ลบสินค้า</button></div></dialog>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/app.js" defer></script>
</body>
</html>
