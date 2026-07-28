// ─── Config ───────────────────────────────────────────────────────────────────
// API_BASE is empty so all fetch calls use relative URLs.
// The PHP server handles both the UI (/ and /app) and the API (/api/*),
// so relative paths always resolve to the same origin — no hardcoded localhost needed.
const API_BASE = '';

// ─── Session-based auth guard ─────────────────────────────────────────────────
// Access is controlled by the token generator on the portal (/).
// Redirect back if no valid session token is found.
(function enforceTokenGate() {
  const token  = sessionStorage.getItem('cookbook_access_token');
  const expiry = parseInt(sessionStorage.getItem('cookbook_token_expiry') || '0');
  if (!token || Date.now() > expiry) {
    sessionStorage.removeItem('cookbook_access_token');
    sessionStorage.removeItem('cookbook_token_expiry');
    sessionStorage.removeItem('cookbook_display_token');
    window.location.replace('/');   // back to the PHP-served portal
  }
})();

const API_TOKEN = sessionStorage.getItem('cookbook_access_token');

const headers = {
  'Authorization': `Bearer ${API_TOKEN}`,
  'Content-Type': 'application/json',
};

// ─── State ────────────────────────────────────────────────────────────────────
let allFoods       = [];
let allIngredients = [];
let currentView    = 'welcome';

// ─── API status ping ──────────────────────────────────────────────────────────
// Pings /api/status (a public, no-auth route) to check if the PHP server is up.
async function pingAPI() {
  try {
    const r = await fetch(`${API_BASE}/api/status`, { method: 'GET' });
    if (r.ok) {
      document.getElementById('statusDot').className   = 'dot';
      document.getElementById('statusText').textContent = 'API online';
    } else { throw new Error(); }
  } catch {
    document.getElementById('statusDot').className   = 'dot red';
    document.getElementById('statusText').textContent = 'API offline';
  }
}

// ─── View router ──────────────────────────────────────────────────────────────
const titles = {
  welcome:     'Overview',
  foods:       'All Foods',
  categories:  'Categories',
  ingredients: 'Ingredients',
  add:         'Add Food',
};

const VIEWS = ['welcome', 'foods', 'categories', 'ingredients', 'add'];

function showView(name) {
  currentView = name;

  document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
  const activeBtn = document.getElementById('nav-' + name);
  if (activeBtn) activeBtn.classList.add('active');

  document.getElementById('pageTitle').textContent = titles[name] || name;

  const searchWrap = document.getElementById('searchWrap');
  if (name === 'foods') {
    searchWrap.classList.remove('hidden');
  } else {
    searchWrap.classList.add('hidden');
  }

  VIEWS.forEach(v => {
    const el = document.getElementById('view-' + v);
    if (!el) return;
    if (v === name) {
      el.style.display = 'block';
      el.animate(
        [{ opacity: 0, transform: 'translateY(6px)' }, { opacity: 1, transform: 'translateY(0)' }],
        { duration: 220, easing: 'cubic-bezier(0.2,0.8,0.2,1)', fill: 'forwards' }
      );
    } else {
      el.style.display = 'none';
    }
  });

  hideError();
  if (name === 'foods')       loadFoods();
  if (name === 'categories')  loadCategories();
  if (name === 'ingredients') loadIngredients();
  if (name === 'add')         loadAddForm();
}

// ─── Error helpers ────────────────────────────────────────────────────────────
function showError(msg) {
  const el = document.getElementById('errorBanner');
  el.textContent = msg;
  el.classList.add('show');
  el.animate(
    [{ opacity: 0, transform: 'translateY(-4px)' }, { opacity: 1, transform: 'translateY(0)' }],
    { duration: 220, easing: 'ease-out', fill: 'forwards' }
  );
}
function hideError() {
  document.getElementById('errorBanner').classList.remove('show');
}

// ─── Rate-limit error helper ──────────────────────────────────────────────────
// Shows a specific message when the server returns 429.
function handleRateLimit(r) {
  if (r.status === 429) {
    showError('⚠️ Rate limit reached (60 req/min). Please wait a moment before trying again.');
    return true;
  }
  return false;
}

// ─── FOODS ────────────────────────────────────────────────────────────────────
async function loadFoods() {
  document.getElementById('foodsContent').innerHTML =
    `<div class="loader"><div class="spinner"></div> Fetching dishes…</div>`;
  try {
    const r = await fetch(`${API_BASE}/api/foods`, { headers });
    if (handleRateLimit(r)) { document.getElementById('foodsContent').innerHTML = ''; return; }
    if (!r.ok) throw new Error(`HTTP ${r.status}`);
    allFoods = await r.json();
    renderFoods(allFoods);
  } catch (e) {
    showError('Could not reach the API. Make sure the PHP server is running.');
    document.getElementById('foodsContent').innerHTML = '';
  }
}

function renderFoods(foods) {
  const container = document.getElementById('foodsContent');
  if (!foods.length) {
    container.innerHTML = '<div class="empty">No dishes found.</div>';
    return;
  }
  container.innerHTML = `<div class="food-grid">${foods.map(f => foodCard(f)).join('')}</div>`;

  container.querySelectorAll('.food-card').forEach((card, i) => {
    card.animate(
      [{ opacity: 0, transform: 'translateY(10px)' }, { opacity: 1, transform: 'translateY(0)' }],
      { duration: 220, delay: i * 45, easing: 'cubic-bezier(0.2,0.8,0.2,1)', fill: 'forwards' }
    );
  });
}

function foodCard(f) {
  const pills = f.ingredients.slice(0, 5)
    .map(i => `<span class="ing-pill">${i}</span>`).join('');
  const more  = f.ingredients.length > 5
    ? `<span class="ing-more">+${f.ingredients.length - 5} more</span>` : '';
  return `
    <div class="food-card"
         onclick="openModal(${f.food_id})"
         tabindex="0"
         onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();openModal(${f.food_id});}">
      <div class="card-header">
        <div class="card-id">#${f.food_id}</div>
        <div class="card-badge">${f.category_name}</div>
        <div class="card-name">${f.food_name}</div>
        <div class="card-origin">📍 ${f.origin_name}</div>
      </div>
      <div class="card-body">
        <div class="card-instructions">${f.instructions}</div>
        <div class="card-ingredients">${pills}${more}</div>
      </div>
    </div>`;
}

// ─── SEARCH ───────────────────────────────────────────────────────────────────
let searchTimer;
async function handleSearch(val) {
  clearTimeout(searchTimer);
  if (!val.trim()) { renderFoods(allFoods); return; }
  searchTimer = setTimeout(async () => {
    try {
      const r = await fetch(
        `${API_BASE}/api/foods/search/${encodeURIComponent(val.trim())}`,
        { headers }
      );
      if (handleRateLimit(r)) return;
      const d = await r.json();
      renderFoods(Array.isArray(d) ? d : []);
    } catch { renderFoods([]); }
  }, 300);
}

// ─── CATEGORIES ───────────────────────────────────────────────────────────────
async function loadCategories() {
  document.getElementById('categoriesContent').innerHTML =
    `<div class="loader"><div class="spinner"></div> Loading…</div>`;
  try {
    const r = await fetch(`${API_BASE}/api/categories`, { headers });
    if (handleRateLimit(r)) { document.getElementById('categoriesContent').innerHTML = ''; return; }
    const d = await r.json();
    document.getElementById('categoriesContent').innerHTML = `
      <div class="simple-list">
        ${d.map(c => `<div class="list-item"><span class="list-id">${c.category_id}</span>${c.category_name}</div>`).join('')}
      </div>`;
  } catch {
    showError('Could not load categories. Is the API running?');
    document.getElementById('categoriesContent').innerHTML = '';
  }
}

// ─── INGREDIENTS ──────────────────────────────────────────────────────────────
async function loadIngredients() {
  document.getElementById('ingredientsContent').innerHTML =
    `<div class="loader"><div class="spinner"></div> Loading…</div>`;
  try {
    const r = await fetch(`${API_BASE}/api/ingredients`, { headers });
    if (handleRateLimit(r)) { document.getElementById('ingredientsContent').innerHTML = ''; return; }
    allIngredients = await r.json();
    document.getElementById('ingredientsContent').innerHTML = `
      <div class="simple-list">
        ${allIngredients.map(i => `<div class="list-item"><span class="list-id">${i.ingredient_id}</span>${i.ingredient_name}</div>`).join('')}
      </div>`;
  } catch {
    showError('Could not load ingredients. Is the API running?');
    document.getElementById('ingredientsContent').innerHTML = '';
  }
}

// ─── ADD FOOD FORM ────────────────────────────────────────────────────────────
async function loadAddForm() {
  try {
    const r = await fetch(`${API_BASE}/api/categories`, { headers });
    if (!r.ok) throw new Error();
    const cats = await r.json();
    document.getElementById('addCategory').innerHTML =
      '<option value="">— select —</option>' +
      cats.map(c => `<option value="${c.category_id}">${c.category_name}</option>`).join('');
  } catch {}

  try {
    const r = await fetch(`${API_BASE}/api/ingredients`, { headers });
    if (!r.ok) throw new Error();
    allIngredients = await r.json();
    document.getElementById('ingCheckGrid').innerHTML = allIngredients.map(i => `
      <label class="ing-check">
        <input type="checkbox" name="ing" value="${i.ingredient_id}" />
        ${i.ingredient_name}
      </label>`).join('');
  } catch {
    document.getElementById('ingCheckGrid').innerHTML =
      '<div style="font-size:12px;opacity:.5">Could not load ingredients</div>';
  }
}

async function submitFood() {
  const btn  = document.getElementById('addBtn');
  const name = document.getElementById('addName').value.trim();
  const cat  = document.getElementById('addCategory').value;
  const orig = document.getElementById('addOrigin').value;
  const ins  = document.getElementById('addInstructions').value.trim();
  const ings = [...document.querySelectorAll('#ingCheckGrid input:checked')]
               .map(el => parseInt(el.value));

  if (!name || !cat || !orig || !ins) {
    showError('Please fill in all required fields.');
    return;
  }
  hideError();

  btn.disabled    = true;
  btn.textContent = 'Adding…';
  try {
    const r = await fetch(`${API_BASE}/api/foods`, {
      method: 'POST',
      headers,
      body: JSON.stringify({
        food_name:      name,
        category_id:    parseInt(cat),
        origin_id:      parseInt(orig),
        instructions:   ins,
        ingredient_ids: ings,
      }),
    });

    if (handleRateLimit(r)) { btn.disabled = false; btn.textContent = 'Add to Cookbook'; return; }

    const d = await r.json();
    if (r.status === 201) {
      document.getElementById('addSuccess').classList.add('show');
      document.getElementById('addName').value = '';
      document.getElementById('addCategory').value = '';
      document.getElementById('addOrigin').value = '';
      document.getElementById('addInstructions').value = '';
      document.querySelectorAll('#ingCheckGrid input').forEach(el => el.checked = false);
      allFoods = [];
    } else {
      showError(d.message || 'Failed to add food.');
    }
  } catch {
    showError('Network error — check that the PHP server is running.');
  }
  btn.disabled    = false;
  btn.textContent = 'Add to Cookbook';
}

// ─── MODAL ────────────────────────────────────────────────────────────────────
async function openModal(id) {
  document.getElementById('modalOverlay').classList.add('open');
  document.getElementById('mName').textContent   = 'Loading…';
  document.getElementById('mCat').textContent    = '';
  document.getElementById('mOrigin').textContent = '';
  document.getElementById('mInstr').textContent  = '';
  document.getElementById('mIngs').innerHTML     = '';

  try {
    const r = await fetch(`${API_BASE}/api/foods/${id}`, { headers });
    if (handleRateLimit(r)) { document.getElementById('mName').textContent = 'Rate limited.'; return; }
    const f = await r.json();
    document.getElementById('mCat').textContent    = f.category_name;
    document.getElementById('mName').textContent   = f.food_name;
    document.getElementById('mOrigin').textContent = '📍 ' + f.origin_name;
    document.getElementById('mInstr').textContent  = f.instructions;
    document.getElementById('mIngs').innerHTML     =
      f.ingredients.map(i => `<span class="modal-ing-pill">${i}</span>`).join('');
  } catch {
    document.getElementById('mName').textContent = 'Could not load details.';
  }
}

function closeModal(e) {
  if (e && e.target !== document.getElementById('modalOverlay')) return;
  document.getElementById('modalOverlay').classList.remove('open');
}

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') document.getElementById('modalOverlay').classList.remove('open');
});

// ─── Init ─────────────────────────────────────────────────────────────────────
pingAPI();
showView('welcome');
