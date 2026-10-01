const API = 'api';
const state = { products: [], categories: [], suppliers: [], query: '', category: '', deleteId: null };
const $ = (selector) => document.querySelector(selector);
const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char]));

async function api(path, options = {}) {
  const response = await fetch(`${API}/${path}`, { ...options, headers: { 'Content-Type': 'application/json', ...(options.headers || {}) } });
  const result = await response.json();
  if (!response.ok || !result.success) {
    const error = new Error(result.message || 'เกิดข้อผิดพลาด กรุณาลองอีกครั้ง');
    error.errors = result.errors || {};
    error.status = response.status;
    throw error;
  }
  return result.data;
}

function toast(message, type = 'success') {
  const item = document.createElement('div');
  item.className = `toast ${type}`;
  item.innerHTML = `<span class="toast-check">${type === 'success' ? '✓' : '!'}</span><span>${escapeHtml(message)}</span>`;
  $('#toastRegion').append(item);
  requestAnimationFrame(() => item.classList.add('visible'));
  setTimeout(() => { item.classList.remove('visible'); setTimeout(() => item.remove(), 250); }, 3600);
}

function formatPrice(price) {
  return new Intl.NumberFormat('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(price));
}

function initials(name = '') {
  return name.split(/\s+/).filter(Boolean).slice(0, 2).map((part) => part[0]).join('').toUpperCase() || 'P';
}

function renderProducts() {
  const query = state.query.toLocaleLowerCase();
  const products = state.products.filter((product) => {
    const matchesQuery = !query || `${product.i_ProductID} ${product.c_ProductName} ${product.c_Unit} ${product.c_SupplierName}`.toLocaleLowerCase().includes(query);
    const matchesCategory = !state.category || String(product.i_CategoryID) === state.category;
    return matchesQuery && matchesCategory;
  });
  $('#resultCount').textContent = `${products.length} รายการ`;
  $('#tableSummary').textContent = `แสดง ${products.length ? 1 : 0}–${products.length} จาก ${state.products.length} รายการ`;
  if (!products.length) {
    $('#productRows').innerHTML = `<tr><td colspan="6" class="empty-cell"><div class="empty-icon">⌕</div><strong>${state.products.length ? 'ไม่พบสินค้าที่ตรงกับการค้นหา' : 'ยังไม่มีสินค้า'}</strong><span>${state.products.length ? 'ลองเปลี่ยนคำค้นหาหรือตัวกรอง' : 'เพิ่มสินค้าแรกของคุณเพื่อเริ่มต้น'}</span></td></tr>`;
    return;
  }
  $('#productRows').innerHTML = products.map((product, index) => `<tr style="--row:${index}">
    <td><div class="product-cell"><div class="product-avatar avatar-${index % 5}">${escapeHtml(initials(product.c_ProductName))}</div><div><div class="product-name">${escapeHtml(product.c_ProductName)}</div><div class="product-id">ID ${String(product.i_ProductID).padStart(3, '0')}</div></div></div></td>
    <td><span class="category-chip">${escapeHtml(product.c_CategoryName || 'ไม่มีหมวดหมู่')}</span></td>
    <td class="supplier-name">${escapeHtml(product.c_SupplierName || '—')}</td><td class="unit-name">${escapeHtml(product.c_Unit)}</td>
    <td class="align-right price-cell">฿${formatPrice(product.i_Price)}</td>
    <td><div class="row-actions"><button class="row-action edit-action" data-action="edit" data-id="${product.i_ProductID}" aria-label="แก้ไข ${escapeHtml(product.c_ProductName)}" title="แก้ไข">↗</button><button class="row-action delete-action" data-action="delete" data-id="${product.i_ProductID}" aria-label="ลบ ${escapeHtml(product.c_ProductName)}" title="ลบ">⌫</button></div></td>
  </tr>`).join('');
}

function updateStats() {
  const total = state.products.length;
  const average = total ? state.products.reduce((sum, product) => sum + Number(product.i_Price), 0) / total : 0;
  $('#totalProducts').textContent = total.toLocaleString('th-TH');
  $('#totalCategories').textContent = state.categories.length.toLocaleString('th-TH');
  $('#averagePrice').textContent = `฿${formatPrice(average)}`;
}

async function loadProducts(showError = true) {
  $('#productRows').innerHTML = '<tr><td colspan="6" class="loading-cell"><span class="spinner"></span> กำลังโหลดข้อมูลสินค้า...</td></tr>';
  try {
    state.products = await api(`products?${new URLSearchParams({ search: state.query, category_id: state.category })}`);
    renderProducts();
    updateStats();
  } catch (error) {
    $('#productRows').innerHTML = `<tr><td colspan="6" class="empty-cell error-cell"><div class="empty-icon">!</div><strong>เชื่อมต่อข้อมูลไม่สำเร็จ</strong><span>${escapeHtml(error.message)}</span></td></tr>`;
    if (showError) toast(error.message, 'error');
  }
}

async function loadLookups() {
  try {
    [state.categories, state.suppliers] = await Promise.all([api('categories'), api('suppliers')]);
    $('#categoryFilter').insertAdjacentHTML('beforeend', state.categories.map((item) => `<option value="${item.i_CategoryID}">${escapeHtml(item.c_CategoryName)}</option>`).join(''));
    $('#productCategory').insertAdjacentHTML('beforeend', state.categories.map((item) => `<option value="${item.i_CategoryID}">${escapeHtml(item.c_CategoryName)}</option>`).join(''));
    $('#productSupplier').insertAdjacentHTML('beforeend', state.suppliers.map((item) => `<option value="${item.i_SupplierID}">${escapeHtml(item.c_SupplierName)}</option>`).join(''));
    updateStats();
  } catch (error) { toast(error.message, 'error'); }
}

function clearFormErrors() {
  document.querySelectorAll('.field-error').forEach((node) => { node.textContent = ''; });
  document.querySelectorAll('.field.invalid').forEach((node) => node.classList.remove('invalid'));
  $('#formMessage').textContent = '';
  $('#formMessage').classList.remove('visible');
}

function showFormErrors(error) {
  clearFormErrors();
  const labels = { c_ProductName: '#productName', i_CategoryID: '#productCategory', i_SupplierID: '#productSupplier', c_Unit: '#productUnit', i_Price: '#productPrice' };
  const entries = Object.entries(error.errors || {});
  if (!entries.length) {
    $('#formMessage').textContent = error.message;
    $('#formMessage').classList.add('visible');
    return;
  }
  entries.forEach(([key, message]) => {
    const input = $(labels[key]);
    if (input) { input.closest('.field').classList.add('invalid'); input.closest('.field').querySelector('.field-error').textContent = message; }
  });
  $('#formMessage').textContent = '請檢查標示的欄位並修正後再儲存';
  $('#formMessage').classList.add('visible');
}

function openCreate() {
  $('#productForm').reset(); $('#productId').value = ''; clearFormErrors();
  $('#dialogTitle').textContent = 'เพิ่มสินค้าใหม่'; $('#saveButton').innerHTML = 'บันทึกสินค้า <span>→</span>';
  $('#productDialog').showModal(); $('#productName').focus();
}

function openEdit(product) {
  $('#productForm').reset(); clearFormErrors();
  $('#productId').value = product.i_ProductID;
  $('#productName').value = product.c_ProductName;
  $('#productCategory').value = product.i_CategoryID;
  $('#productSupplier').value = product.i_SupplierID;
  $('#productUnit').value = product.c_Unit;
  $('#productPrice').value = Number(product.i_Price);
  $('#dialogTitle').textContent = 'แก้ไขข้อมูลสินค้า'; $('#saveButton').innerHTML = 'บันทึกการแก้ไข <span>→</span>';
  $('#productDialog').showModal(); $('#productName').focus();
}

$('#addProduct').addEventListener('click', openCreate);
$('#closeDialog').addEventListener('click', () => $('#productDialog').close());
$('#cancelDialog').addEventListener('click', () => $('#productDialog').close());
$('#productDialog').addEventListener('click', (event) => { if (event.target === $('#productDialog')) $('#productDialog').close(); });
$('#productForm').addEventListener('input', (event) => {
  const field = event.target.closest('.field');
  if (field?.classList.contains('invalid')) { field.classList.remove('invalid'); field.querySelector('.field-error').textContent = ''; }
});
$('#productForm').addEventListener('submit', async (event) => {
  event.preventDefault(); clearFormErrors();
  const form = event.currentTarget;
  if (!form.reportValidity()) return;
  const id = $('#productId').value;
  const payload = Object.fromEntries(new FormData(form).entries());
  payload.i_CategoryID = Number(payload.i_CategoryID); payload.i_SupplierID = Number(payload.i_SupplierID); payload.i_Price = Number(payload.i_Price);
  const button = $('#saveButton'); button.disabled = true; button.innerHTML = '<span class="spinner spinner-light"></span> กำลังบันทึก';
  try {
    const result = await api(id ? `products/${id}` : 'products', { method: id ? 'PUT' : 'POST', body: JSON.stringify(payload) });
    $('#productDialog').close(); toast(result.c_ProductName ? (id ? 'แก้ไขข้อมูลสินค้าสำเร็จ' : 'เพิ่มสินค้าเรียบร้อยแล้ว') : 'บันทึกข้อมูลสำเร็จ');
    await loadProducts(false);
  } catch (error) { showFormErrors(error); if (!Object.keys(error.errors || {}).length) toast(error.message, 'error'); }
  finally { button.disabled = false; button.innerHTML = id ? 'บันทึกการแก้ไข <span>→</span>' : 'บันทึกสินค้า <span>→</span>'; }
});

$('#productRows').addEventListener('click', (event) => {
  const button = event.target.closest('[data-action]'); if (!button) return;
  const product = state.products.find((item) => String(item.i_ProductID) === button.dataset.id); if (!product) return;
  if (button.dataset.action === 'edit') openEdit(product);
  if (button.dataset.action === 'delete') {
    state.deleteId = product.i_ProductID;
    $('#deleteMessage').textContent = `“${product.c_ProductName}” จะถูกนำออกจากรายการสินค้า`;
    $('#deleteDialog').showModal();
  }
});
$('#cancelDelete').addEventListener('click', () => $('#deleteDialog').close());
$('#confirmDelete').addEventListener('click', async () => {
  const button = $('#confirmDelete'); button.disabled = true;
  try {
    const result = await api(`products/${state.deleteId}`, { method: 'DELETE' });
    $('#deleteDialog').close(); toast(result.message || 'ลบสินค้าเรียบร้อยแล้ว'); await loadProducts(false);
  } catch (error) { $('#deleteDialog').close(); toast(error.message, 'error'); }
  finally { button.disabled = false; state.deleteId = null; }
});

let searchTimer;
$('#searchInput').addEventListener('input', (event) => { state.query = event.target.value.trim(); clearTimeout(searchTimer); searchTimer = setTimeout(() => loadProducts(false), 220); });
$('#categoryFilter').addEventListener('change', (event) => { state.category = event.target.value; loadProducts(false); });
$('#refreshButton').addEventListener('click', () => loadProducts());
const themeToggle = $('#themeToggle');
const themeIcon = $('#themeIcon');
const savedTheme = localStorage.getItem('northwind-theme');
if (savedTheme === 'dark') document.documentElement.setAttribute('data-bs-theme', 'dark');
themeIcon.className = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'bi bi-sun-fill text-warning fs-5' : 'bi bi-moon-stars-fill text-warning fs-5';
themeToggle.addEventListener('click', () => {
  const nextTheme = document.documentElement.getAttribute('data-bs-theme') === 'light' ? 'dark' : 'light';
  document.documentElement.setAttribute('data-bs-theme', nextTheme);
  localStorage.setItem('northwind-theme', nextTheme);
  themeIcon.className = nextTheme === 'dark' ? 'bi bi-sun-fill text-warning fs-5' : 'bi bi-moon-stars-fill text-warning fs-5';
});
document.addEventListener('keydown', (event) => {
  if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') { event.preventDefault(); $('#searchInput').focus(); }
  if (event.key === 'Escape') { $('#productDialog').close(); $('#deleteDialog').close(); }
});

Promise.all([loadLookups(), loadProducts()]);
