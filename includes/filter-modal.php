<?php
// Filter & Sorting Modal Component (Zomato/Swiggy Dark UI Style)

$current_sort = $_GET['sort'] ?? 'relevance';
$current_time = $_GET['time'] ?? '';
$current_price_range = $_GET['price_range'] ?? '';
$current_trust = $_GET['trust'] ?? '';

// Calculate count of active filters
$active_filter_count = 0;
if (!empty($current_sort) && $current_sort !== 'relevance') $active_filter_count++;
if (!empty($current_time)) $active_filter_count++;
if (!empty($current_price_range)) $active_filter_count++;
if (!empty($current_trust)) $active_filter_count++;
?>

<!-- Filter & Sorting Modal -->
<div class="modal fade" id="filterSortModal" tabindex="-1" aria-labelledby="filterSortModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
    <div class="modal-content border-0 filter-modal-dark shadow-2xl overflow-hidden" style="border-radius: 24px; background-color: #121318; color: #ffffff;">
      
      <!-- Modal Header -->
      <div class="modal-header border-0 px-4 pt-4 pb-3 d-flex justify-content-between align-items-center" style="border-bottom: 1px solid rgba(255,255,255,0.08) !important;">
        <h4 class="modal-title fw-bold text-white mb-0 fs-5" id="filterSortModalLabel">
          Filters and sorting
        </h4>
        <button type="button" class="btn btn-link text-decoration-none text-secondary p-0 fw-semibold text-sm hover-white" onclick="clearAllFilters()">
          Clear All
        </button>
      </div>

      <!-- Modal Body with Split View (Left Tabs, Right Content) -->
      <div class="modal-body p-0" style="min-height: 400px; background-color: #121318;">
        <form id="filterSortForm" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="GET" class="h-100 d-flex flex-column flex-md-row">
          
          <!-- Preserve existing search & category context -->
          <?php if (!empty($_GET['search'])): ?>
            <input type="hidden" name="search" value="<?php echo htmlspecialchars($_GET['search']); ?>">
          <?php endif; ?>
          <?php if (!empty($_GET['catagory'])): ?>
            <input type="hidden" name="catagory" value="<?php echo intval($_GET['catagory']); ?>">
          <?php endif; ?>
          <?php if (!empty($_GET['restaurant_id'])): ?>
            <input type="hidden" name="restaurant_id" value="<?php echo intval($_GET['restaurant_id']); ?>">
          <?php endif; ?>

          <!-- Hidden input fields for active selections -->
          <input type="hidden" id="inputSort" name="sort" value="<?php echo htmlspecialchars($current_sort); ?>">
          <input type="hidden" id="inputTime" name="time" value="<?php echo htmlspecialchars($current_time); ?>">
          <input type="hidden" id="inputPriceRange" name="price_range" value="<?php echo htmlspecialchars($current_price_range); ?>">
          <input type="hidden" id="inputTrust" name="trust" value="<?php echo htmlspecialchars($current_trust); ?>">

          <!-- Left Vertical Tab Navigation -->
          <div class="filter-sidebar d-flex flex-row flex-md-column" style="width: 100%; max-width: 220px; border-right: 1px solid rgba(255,255,255,0.08); background-color: #191b22;">
            <button type="button" class="filter-tab-btn active text-start py-3 px-3 d-flex align-items-center gap-2.5 text-secondary fw-semibold border-0 w-100 bg-transparent" onclick="switchFilterTab('sort_by', this)">
              <i class="fa-solid fa-arrow-down-short-wide fs-5"></i>
              <span>Sort By</span>
            </button>
            <button type="button" class="filter-tab-btn text-start py-3 px-3 d-flex align-items-center gap-2.5 text-secondary fw-semibold border-0 w-100 bg-transparent" onclick="switchFilterTab('time', this)">
              <i class="fa-regular fa-clock fs-5"></i>
              <span>Time</span>
            </button>
            <button type="button" class="filter-tab-btn text-start py-3 px-3 d-flex align-items-center gap-2.5 text-secondary fw-semibold border-0 w-100 bg-transparent" onclick="switchFilterTab('dish_price', this)">
              <i class="fa-solid fa-indian-rupee-sign fs-5"></i>
              <span>Dish Price</span>
            </button>
            <button type="button" class="filter-tab-btn text-start py-3 px-3 d-flex align-items-center gap-2.5 text-secondary fw-semibold border-0 w-100 bg-transparent" onclick="switchFilterTab('trust_markers', this)">
              <i class="fa-solid fa-shield-halved fs-5"></i>
              <span>Trust Markers</span>
            </button>
          </div>

          <!-- Right Content Panes -->
          <div class="filter-content-area flex-grow-1 p-4" style="background-color: #121318; overflow-y: auto;">
            
            <!-- Pane 1: Sort By -->
            <div class="filter-pane" id="pane_sort_by">
              <h6 class="text-white fw-bold mb-3 text-uppercase text-xs tracking-wider" style="color: #94a3b8 !important;">Sort By</h6>
              <div class="d-flex flex-column gap-2">
                
                <div class="filter-option-row p-3 rounded-4 d-flex justify-content-between align-items-center cursor-pointer <?php echo $current_sort === 'relevance' ? 'selected' : ''; ?>" onclick="selectSingleOption('sort', 'relevance', this)">
                  <span class="text-white fw-medium">Relevance (Default)</span>
                  <div class="custom-radio-dot"></div>
                </div>

                <div class="filter-option-row p-3 rounded-4 d-flex justify-content-between align-items-center cursor-pointer <?php echo $current_sort === 'price_asc' ? 'selected' : ''; ?>" onclick="selectSingleOption('sort', 'price_asc', this)">
                  <span class="text-white fw-medium">Price: Low to High (₹ $\rightarrow$ ₹₹₹)</span>
                  <div class="custom-radio-dot"></div>
                </div>

                <div class="filter-option-row p-3 rounded-4 d-flex justify-content-between align-items-center cursor-pointer <?php echo $current_sort === 'price_desc' ? 'selected' : ''; ?>" onclick="selectSingleOption('sort', 'price_desc', this)">
                  <span class="text-white fw-medium">Price: High to Low (₹₹₹ $\rightarrow$ ₹)</span>
                  <div class="custom-radio-dot"></div>
                </div>

                <div class="filter-option-row p-3 rounded-4 d-flex justify-content-between align-items-center cursor-pointer <?php echo $current_sort === 'name_asc' ? 'selected' : ''; ?>" onclick="selectSingleOption('sort', 'name_asc', this)">
                  <span class="text-white fw-medium">Dish Name: A to Z</span>
                  <div class="custom-radio-dot"></div>
                </div>

              </div>
            </div>

            <!-- Pane 2: Time -->
            <div class="filter-pane d-none" id="pane_time">
              <h6 class="text-white fw-bold mb-3 text-uppercase text-xs tracking-wider" style="color: #94a3b8 !important;">Delivery Time & Speed</h6>
              <div class="row g-3">
                <div class="col-6">
                  <div class="filter-tile p-4 rounded-4 text-center cursor-pointer <?php echo $current_time === 'schedule' ? 'selected' : ''; ?>" onclick="toggleTileOption('time', 'schedule', this)">
                    <i class="fa-regular fa-calendar-check fs-2 mb-2 d-block text-secondary"></i>
                    <span class="fw-bold d-block text-white">Schedule</span>
                    <span class="text-muted text-xs">Standard Orders</span>
                  </div>
                </div>
                <div class="col-6">
                  <div class="filter-tile p-4 rounded-4 text-center cursor-pointer <?php echo $current_time === 'fast' ? 'selected' : ''; ?>" onclick="toggleTileOption('time', 'fast', this)">
                    <i class="fa-solid fa-bolt fs-2 mb-2 d-block text-success"></i>
                    <span class="fw-bold d-block text-white">Near & Fast</span>
                    <span class="text-muted text-xs">Express Delivery</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Pane 3: Dish Price -->
            <div class="filter-pane d-none" id="pane_dish_price">
              <h6 class="text-white fw-bold mb-3 text-uppercase text-xs tracking-wider" style="color: #94a3b8 !important;">Dish Price Range</h6>
              <div class="row g-3">
                <div class="col-4">
                  <div class="filter-tile p-3 rounded-4 text-center cursor-pointer <?php echo $current_price_range === 'under_200' ? 'selected' : ''; ?>" onclick="toggleTileOption('price_range', 'under_200', this)">
                    <span class="fs-4 fw-bold text-success d-block mb-1">₹</span>
                    <span class="fw-bold d-block text-white small">Under ₹200</span>
                    <span class="text-muted text-xs">Budget bites</span>
                  </div>
                </div>
                <div class="col-4">
                  <div class="filter-tile p-3 rounded-4 text-center cursor-pointer <?php echo $current_price_range === '200_350' ? 'selected' : ''; ?>" onclick="toggleTileOption('price_range', '200_350', this)">
                    <span class="fs-4 fw-bold text-success d-block mb-1">₹₹</span>
                    <span class="fw-bold d-block text-white small">₹200 - ₹350</span>
                    <span class="text-muted text-xs">Regular meals</span>
                  </div>
                </div>
                <div class="col-4">
                  <div class="filter-tile p-3 rounded-4 text-center cursor-pointer <?php echo $current_price_range === 'above_350' ? 'selected' : ''; ?>" onclick="toggleTileOption('price_range', 'above_350', this)">
                    <span class="fs-4 fw-bold text-success d-block mb-1">₹₹₹</span>
                    <span class="fw-bold d-block text-white small">Above ₹350</span>
                    <span class="text-muted text-xs">Gourmet & Combos</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Pane 5: Trust Markers -->
            <div class="filter-pane d-none" id="pane_trust_markers">
              <h6 class="text-white fw-bold mb-3 text-uppercase text-xs tracking-wider" style="color: #94a3b8 !important;">Trust & Hygiene</h6>
              <div class="row g-3">
                <div class="col-6">
                  <div class="filter-tile p-4 rounded-4 text-center cursor-pointer <?php echo $current_trust === 'verified' ? 'selected' : ''; ?>" onclick="toggleTileOption('trust', 'verified', this)">
                    <i class="fa-solid fa-certificate fs-2 mb-2 d-block text-success"></i>
                    <span class="fw-bold d-block text-white">Verified Kitchens</span>
                    <span class="text-muted text-xs">100% License Inspected</span>
                  </div>
                </div>
                <div class="col-6">
                  <div class="filter-tile p-4 rounded-4 text-center cursor-pointer <?php echo $current_trust === 'pure_veg' ? 'selected' : ''; ?>" onclick="toggleTileOption('trust', 'pure_veg', this)">
                    <i class="fa-solid fa-leaf fs-2 mb-2 d-block text-success"></i>
                    <span class="fw-bold d-block text-white">100% Pure Veg</span>
                    <span class="text-muted text-xs">Certified Vegetarian</span>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </form>
      </div>

      <!-- Modal Footer Action Bar -->
      <div class="modal-footer border-0 px-4 py-3 d-flex justify-content-between align-items-center" style="border-top: 1px solid rgba(255,255,255,0.08) !important; background-color: #121318;">
        <button type="button" class="btn btn-link text-decoration-none text-light fw-bold px-3 py-2" data-bs-dismiss="modal">
          Close
        </button>
        <button type="button" class="btn btn-secondary text-white fw-bold px-5 py-2.5 rounded-pill shadow-sm" style="background-color: #3b3f4f; border-color: #4a4f63;" onclick="applyFiltersAndSubmit()">
          Show results
        </button>
      </div>

    </div>
  </div>
</div>

<style>
/* Custom Zomato/Swiggy Dark Theme Filter Modal */
.filter-modal-dark {
  font-family: system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}
.filter-tab-btn {
  position: relative;
  transition: all 0.2s ease;
  color: #94a3b8 !important;
}
.filter-tab-btn:hover {
  color: #ffffff !important;
  background-color: rgba(255, 255, 255, 0.04) !important;
}
.filter-tab-btn.active {
  color: #ffffff !important;
  background-color: #121318 !important;
}
.filter-tab-btn.active::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 4px;
  background-color: #ef4444; /* Active tab red/crimson bar */
  border-radius: 0 4px 4px 0;
}
.filter-tile {
  background-color: #1e2029;
  border: 1.5px solid #2a2d3a;
  transition: all 0.2s ease;
}
.filter-tile:hover {
  background-color: #262934;
  border-color: #3b3f4f;
}
.filter-tile.selected {
  background-color: #1f2722;
  border-color: #10b981;
  box-shadow: 0 0 0 1px #10b981;
}
.filter-option-row {
  background-color: #1e2029;
  border: 1.5px solid #2a2d3a;
  transition: all 0.2s ease;
}
.filter-option-row:hover {
  background-color: #262934;
  border-color: #3b3f4f;
}
.filter-option-row.selected {
  background-color: #1f2722;
  border-color: #10b981;
}
.custom-radio-dot {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  border: 2px solid #64748b;
  position: relative;
  transition: all 0.2s ease;
}
.filter-option-row.selected .custom-radio-dot {
  border-color: #10b981;
  background-color: #10b981;
}
.filter-option-row.selected .custom-radio-dot::after {
  content: '';
  position: absolute;
  top: 4px;
  left: 4px;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background-color: #ffffff;
}
.cursor-pointer {
  cursor: pointer;
}
.text-xs {
  font-size: 0.75rem;
}
.hover-white:hover {
  color: #ffffff !important;
}
</style>

<script>
function switchFilterTab(paneId, btn) {
    document.querySelectorAll('.filter-tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    document.querySelectorAll('.filter-pane').forEach(p => p.classList.add('d-none'));
    const activePane = document.getElementById('pane_' + paneId);
    if (activePane) activePane.classList.remove('d-none');
}

function selectSingleOption(field, value, el) {
    const hiddenInput = document.getElementById('input' + capitalizeFirstLetter(field));
    if (hiddenInput) {
        hiddenInput.value = value;
    }
    const parentPane = el.closest('.filter-pane');
    if (parentPane) {
        parentPane.querySelectorAll('.filter-option-row').forEach(r => r.classList.remove('selected'));
    }
    el.classList.add('selected');
}

function toggleTileOption(field, value, el) {
    const inputId = 'input' + field.split('_').map(capitalizeFirstLetter).join('');
    const hiddenInput = document.getElementById(inputId);
    if (!hiddenInput) return;

    if (hiddenInput.value === value) {
        // Deselect
        hiddenInput.value = '';
        el.classList.remove('selected');
    } else {
        const parentPane = el.closest('.filter-pane');
        if (parentPane) {
            parentPane.querySelectorAll('.filter-tile').forEach(t => t.classList.remove('selected'));
        }
        hiddenInput.value = value;
        el.classList.add('selected');
    }
}

function clearAllFilters() {
    document.getElementById('inputSort').value = 'relevance';
    document.getElementById('inputTime').value = '';
    document.getElementById('inputPriceRange').value = '';
    document.getElementById('inputTrust').value = '';

    document.querySelectorAll('.filter-tile, .filter-option-row').forEach(el => el.classList.remove('selected'));
    const defaultSort = document.querySelector('#pane_sort_by .filter-option-row');
    if (defaultSort) defaultSort.classList.add('selected');

    document.getElementById('filterSortForm').submit();
}

function applyFiltersAndSubmit() {
    document.getElementById('filterSortForm').submit();
}

function capitalizeFirstLetter(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}
</script>
