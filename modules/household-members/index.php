<?php
$pageTitle = 'Household Members';
require_once __DIR__ . '/../../includes/header.php';

$db = getDB();

// ── Filters ────────────────────────────────────────────────────
$search     = trim($_GET['search'] ?? '');
$household  = trim($_GET['household'] ?? '');
$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = 10;

$where  = [];
$params = [];

if ($search) {
    $where[]  = '(m.name LIKE ? OR m.occupation LIKE ?)';
    $params   = array_merge($params, ["%$search%", "%$search%"]);
}
if ($household) {
    $where[]  = 'm.household_id = ?';
    $params[] = $household;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count
$countStmt = $db->prepare("SELECT COUNT(*) FROM household_members m $whereSQL");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$pages = ceil($total / $perPage);
$offset= ($page - 1) * $perPage;

// Fetch
$stmt = $db->prepare("SELECT m.*, h.family_name FROM household_members m
    LEFT JOIN households h ON m.household_id = h.id
    $whereSQL ORDER BY h.family_name, m.name ASC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Household list for filter
$households = $db->query('SELECT id, family_name FROM households ORDER BY family_name')->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<div class="page-header">
    <div>
        <div class="page-header-title"><i class="bi bi-person-badge-fill me-2 text-primary"></i>Household Members</div>
        <div class="page-header-sub">Manage members within households</div>
    </div>
    <?php if (isStaff() || isEncoder()): ?>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#batchModal" onclick="resetBatchForm()">
            <i class="bi bi-plus-lg me-1"></i>Add Members
        </button>
    </div>
    <?php endif; ?>
</div>

<div class="card mb-0" style="border-bottom:0;border-radius:var(--radius) var(--radius) 0 0;">
    <div class="toolbar">
        <form method="GET" class="d-flex gap-2 flex-grow-1">
            <input type="text" name="search" class="form-control" style="max-width:200px;"
                   placeholder="Search member / occupation…" value="<?= clean($search) ?>">
            <select name="household" class="form-select" style="max-width:200px;">
                <option value="">All households</option>
                <?php foreach ($households as $id => $name): ?>
                    <option value="<?= $id ?>" <?= $household == $id ? 'selected' : '' ?>>
                        <?= clean($name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
            <?php if ($search || $household): ?><a href="?" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a><?php endif; ?>
        </form>
        <span class="text-muted" style="font-size:0.8rem;white-space:nowrap;"><?= $total ?> records</span>
    </div>
</div>

<div class="card" style="border-radius:0 0 var(--radius) var(--radius);">
    <div class="table-wrapper">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Household</th>
                    <th>Relationship</th>
                    <th>Age</th>
                    <th>Occupation</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="7" class="empty-state"><i class="bi bi-inbox"></i><p>No household members found.</p></td></tr>
            <?php else:
                $householdCounts = array_count_values(array_column($rows, 'household_id'));
                $currentHousehold = null;
                $rowNumber = $offset + 1;
            ?>
                <?php foreach ($rows as $row): ?>
                    <?php $isNewHousehold = $currentHousehold !== $row['household_id']; ?>
                    <?php if ($isNewHousehold): ?>
                        <?php $currentHousehold = $row['household_id']; ?>
                        <tr class="table-group-header bg-light">
                            <td colspan="7" class="fw-semibold">
                                <span class="me-3"><i class="bi bi-house-door-fill"></i> <?= clean($row['family_name']) ?></span>
                                <span class="badge bg-secondary align-middle"><?= $householdCounts[$row['household_id']] ?> member<?= $householdCounts[$row['household_id']] > 1 ? 's' : '' ?></span>
                                <button type="button" class="btn btn-sm btn-outline-info ms-3" onclick="viewHouseholdMembers(<?= $row['household_id'] ?>, '<?= clean($row['family_name']) ?>')">
                                    <i class="bi bi-eye"></i> View Household
                                </button>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <td class="text-muted" style="font-size:0.8rem;"><?= $rowNumber++ ?></td>
                        <td><strong><?= clean($row['name']) ?></strong></td>
                        <td><?= $isNewHousehold ? clean($row['family_name']) : '&nbsp;' ?></td>
                        <td><?= clean($row['relationship']) ?></td>
                        <td><?= $row['age'] ?? '—' ?></td>
                        <td><?= clean($row['occupation'] ?? '—') ?></td>
                        <td>
                            <div class="d-flex gap-1 align-items-center">
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" 
                                    data-bs-target="#formModal" onclick="editRecord(<?= $row['id'] ?>)" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <?php if (isAdmin()): ?>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteRecord(<?= $row['id'] ?>, '<?= clean($row['name']) ?>')" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($pages > 1): ?>
    <div style="padding:12px 16px;border-top:1px solid var(--border);">
        <nav>
            <ul class="pagination justify-content-center mb-0">
                    <?php for ($i = 1; $i <= $pages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="index.php?page=<?= $i ?><?= $search ? '&search='.urlencode($search) : '' ?><?= $household ? '&household='.$household : '' ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
</div>

<!-- ── Add/Edit Modal ────────────────────────────────────────– -->
<div class="modal fade" id="formModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="recordForm" method="POST" action="save.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="recordId" name="id" value="0">
                    
                    <div class="mb-3">
                        <label for="household_id" class="form-label">Household <span class="text-danger">*</span></label>
                        <select id="household_id" name="household_id" class="form-select" required>
                            <option value="">Select a household...</option>
                            <?php foreach ($households as $id => $name): ?>
                                <option value="<?= $id ?>"><?= clean($name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="name" class="form-label">Member Name <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="relationship" class="form-label">Relationship <span class="text-danger">*</span></label>
                        <select id="relationship" name="relationship" class="form-select" required>
                            <option value="">Select...</option>
                            <option value="Head">Head</option>
                            <option value="Spouse">Spouse</option>
                            <option value="Son">Son</option>
                            <option value="Daughter">Daughter</option>
                            <option value="Father">Father</option>
                            <option value="Mother">Mother</option>
                            <option value="Brother">Brother</option>
                            <option value="Sister">Sister</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="age" class="form-label">Age</label>
                            <input type="number" id="age" name="age" class="form-control" min="0" max="120">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="contact" class="form-label">Contact</label>
                            <input type="tel" id="contact" name="contact" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="occupation" class="form-label">Occupation</label>
                        <input type="text" id="occupation" name="occupation" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="batchModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="batchForm" method="POST" action="save.php">
                <div class="modal-header">
                    <h5 class="modal-title">Add Multiple Members</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="batchHouseholdId" class="form-label">Household <span class="text-danger">*</span></label>
                        <select id="batchHouseholdId" name="household_id" class="form-select" required>
                            <option value="">Select a household...</option>
                            <?php foreach ($households as $id => $name): ?>
                                <option value="<?= $id ?>"><?= clean($name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="batchRows"></div>
                    <button type="button" class="btn btn-sm btn-outline-secondary mb-3" onclick="addBatchRow()">
                        <i class="bi bi-plus-lg"></i> Add another member
                    </button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save All Members</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white rounded-top">
                <div>
                    <h5 class="modal-title" id="viewModalTitle">Household Members</h5>
                    <p class="text-white-75 mb-0" id="viewModalSubtitle">View all family members in this household</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light p-4">
                <div id="viewHouseholdAlert"></div>
                <div class="row g-4">
                    <div class="col-lg-7">
                        <div class="card border shadow-sm rounded-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                                    <div>
                                        <p class="text-uppercase text-muted mb-1">Family Members</p>
                                        <h6 class="mb-0" id="viewMembersCount">0 members</h6>
                                    </div>
                                </div>
                                <div id="viewMembersList"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="card border shadow-sm rounded-4 h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="mb-4 border-bottom pb-3">
                                    <p class="text-uppercase text-muted mb-2">Add New Household Member</p>
                                    <h6 class="fw-semibold mb-0">Quickly add a new family member</h6>
                                </div>
                                <form id="viewAddMemberForm" class="row g-3">
                                    <input type="hidden" id="viewHouseholdId" name="household_id">
                                    <div class="col-12">
                                        <label class="form-label">Name <span class="text-danger">*</span></label>
                                        <input type="text" id="viewMemberName" name="name" class="form-control" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Relationship <span class="text-danger">*</span></label>
                                        <select id="viewMemberRelationship" name="relationship" class="form-select" required>
                                            <option value="">Select relationship</option>
                                            <option value="Head">Head</option>
                                            <option value="Spouse">Spouse</option>
                                            <option value="Son">Son</option>
                                            <option value="Daughter">Daughter</option>
                                            <option value="Father">Father</option>
                                            <option value="Mother">Mother</option>
                                            <option value="Brother">Brother</option>
                                            <option value="Sister">Sister</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Age</label>
                                        <input type="number" id="viewMemberAge" name="age" class="form-control" min="0" max="120">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Occupation</label>
                                        <input type="text" id="viewMemberOccupation" name="occupation" class="form-control">
                                    </div>
                                    <div class="col-12 mb-4">
                                        <label class="form-label">Contact</label>
                                        <input type="tel" id="viewMemberContact" name="contact" class="form-control">
                                    </div>
                                    <div class="col-12 mt-3">
                                        <button type="submit" class="btn btn-primary btn-lg w-100">Add Member</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function resetForm() {
    document.getElementById('recordForm').reset();
    document.getElementById('recordId').value = '0';
    document.getElementById('modalTitle').textContent = 'Add Member';
}

function resetBatchForm() {
    document.getElementById('batchForm').reset();
    document.getElementById('batchRows').innerHTML = '';
    addBatchRow();
}

function addBatchRow(data = {}) {
    const container = document.getElementById('batchRows');
    const row = document.createElement('div');
    row.className = 'batch-member-row row g-3 align-items-end mb-3';
    row.innerHTML = `
        <div class="col-md-3">
            <label class="form-label">Member Name <span class="text-danger">*</span></label>
            <input type="text" name="names[]" class="form-control" required value="${data.name || ''}">
        </div>
        <div class="col-md-2">
            <label class="form-label">Relationship <span class="text-danger">*</span></label>
            <select name="relationships[]" class="form-select" required>
                <option value="">Select...</option>
                <option value="Head" ${data.relationship === 'Head' ? 'selected' : ''}>Head</option>
                <option value="Spouse" ${data.relationship === 'Spouse' ? 'selected' : ''}>Spouse</option>
                <option value="Son" ${data.relationship === 'Son' ? 'selected' : ''}>Son</option>
                <option value="Daughter" ${data.relationship === 'Daughter' ? 'selected' : ''}>Daughter</option>
                <option value="Father" ${data.relationship === 'Father' ? 'selected' : ''}>Father</option>
                <option value="Mother" ${data.relationship === 'Mother' ? 'selected' : ''}>Mother</option>
                <option value="Brother" ${data.relationship === 'Brother' ? 'selected' : ''}>Brother</option>
                <option value="Sister" ${data.relationship === 'Sister' ? 'selected' : ''}>Sister</option>
                <option value="Other" ${data.relationship === 'Other' ? 'selected' : ''}>Other</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Age</label>
            <input type="number" name="ages[]" class="form-control" min="0" max="120" value="${data.age || ''}">
        </div>
        <div class="col-md-2">
            <label class="form-label">Contact</label>
            <input type="text" name="contacts[]" class="form-control" value="${data.contact || ''}">
        </div>
        <div class="col-md-2">
            <label class="form-label">Occupation</label>
            <input type="text" name="occupations[]" class="form-control" value="${data.occupation || ''}">
        </div>
        <div class="col-md-1 d-grid">
            <button type="button" class="btn btn-outline-danger btn-sm remove-batch-row" onclick="removeBatchRow(this)">×</button>
        </div>
    `;
    container.appendChild(row);
    updateBatchRemoveButtons();
}

function removeBatchRow(button) {
    const row = button.closest('.batch-member-row');
    if (row) {
        row.remove();
        updateBatchRemoveButtons();
    }
}

function updateBatchRemoveButtons() {
    const buttons = document.querySelectorAll('#batchRows .remove-batch-row');
    buttons.forEach(btn => {
        btn.disabled = buttons.length === 1;
    });
}

function editRecord(id) {
    fetch(`view.php?id=${id}`)
        .then(r => r.json())
        .then(data => {
            document.getElementById('recordId').value = data.id;
            document.getElementById('household_id').value = data.household_id;
            document.getElementById('name').value = data.name;
            document.getElementById('relationship').value = data.relationship;
            document.getElementById('age').value = data.age || '';
            document.getElementById('contact').value = data.contact || '';
            document.getElementById('occupation').value = data.occupation || '';
            document.getElementById('modalTitle').textContent = 'Edit Member';
            const modal = new bootstrap.Modal(document.getElementById('formModal'));
            modal.show();
        });
}

function deleteRecord(id, name) {
    if (confirm(`Delete "${name}"? This action cannot be undone.`)) {
        fetch('delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${id}`
        }).then(() => window.location.reload());
    }
}

function viewHouseholdMembers(householdId, householdName) {
    fetch(`household.php?household_id=${householdId}`)
        .then(r => r.json())
        .then(data => {
            document.getElementById('viewModalTitle').textContent = `Household: ${householdName}`;
            const memberCount = Array.isArray(data) ? data.length : 0;
            document.getElementById('viewModalSubtitle').textContent = `${memberCount} member${memberCount === 1 ? '' : 's'} in this household`;
            document.getElementById('viewMembersCount').textContent = `${memberCount} member${memberCount === 1 ? '' : 's'}`;
            document.getElementById('viewHouseholdAlert').innerHTML = '';
            document.getElementById('viewHouseholdId').value = householdId;
            document.getElementById('viewMemberName').value = '';
            document.getElementById('viewMemberRelationship').value = '';
            document.getElementById('viewMemberAge').value = '';
            document.getElementById('viewMemberOccupation').value = '';
            document.getElementById('viewMemberContact').value = '';

            const list = document.getElementById('viewMembersList');
            if (!Array.isArray(data) || data.length === 0) {
                list.innerHTML = '<div class="alert alert-secondary py-3">No family members found for this household.</div>';
            } else {
                list.innerHTML = `
                    <div class="table-responsive">
                        <table class="table table-hover table-borderless align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="py-3">#</th>
                                    <th class="py-3">Name</th>
                                    <th class="py-3">Relationship</th>
                                    <th class="py-3">Age</th>
                                    <th class="py-3">Occupation</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.map((member, index) => `
                                    <tr>
                                        <td>${index + 1}</td>
                                        <td>${member.name}</td>
                                        <td>${member.relationship}</td>
                                        <td>${member.age || '—'}</td>
                                        <td>${member.occupation || '—'}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
            }
            const modal = new bootstrap.Modal(document.getElementById('viewModal'));
            modal.show();
        });
}

function submitViewMemberForm(event) {
    event.preventDefault();
    const form = event.target;
    const data = new URLSearchParams(new FormData(form));
    fetch('save.php', {
        method: 'POST',
        body: data,
    })
    .then(r => r.text())
    .then(() => {
        const householdId = document.getElementById('viewHouseholdId').value;
        const householdName = document.getElementById('viewModalTitle').textContent.replace('Household: ', '');
        viewHouseholdMembers(householdId, householdName);
        document.getElementById('viewHouseholdAlert').innerHTML = '<div class="alert alert-success mb-3">Member added successfully.</div>';
    })
    .catch(() => {
        document.getElementById('viewHouseholdAlert').innerHTML = '<div class="alert alert-danger mb-3">Unable to add member. Please try again.</div>';
    });
}

document.addEventListener('DOMContentLoaded', function() {
    resetBatchForm();
    document.getElementById('viewAddMemberForm').addEventListener('submit', submitViewMemberForm);
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
