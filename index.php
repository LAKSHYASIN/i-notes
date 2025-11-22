<?php
// iNotes - Single-file modern notes app (improved)
// Requirements applied: prepared statements, AJAX endpoints, SweetAlert2, dark mode, responsive cards/table, character counters, categories, icons, DataTables.

$insertResult = null;
$updateResult = null;
$deleteResult = null;

// DB connection
$servername = "localhost:3307"; // adjust if needed
$username = "root";
$password = "";
$dbname = "notes";

$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) {
    http_response_code(500);
    die("Connection failed: " . mysqli_connect_error());
}

// Helper: send JSON response for AJAX
function send_json($payload) {
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

// Handle AJAX actions: insert, update, delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    // Retrieve fields safely
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';
    $sno = isset($_POST['sno']) ? intval($_POST['sno']) : 0;
    $isAjax = isset($_POST['ajax']) && $_POST['ajax'] === '1';

    if ($action === 'insert') {
        $sql = "INSERT INTO notes (`title`, `description`, `category`, `tstamp`) VALUES (?, ?, ?, current_timestamp())";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            if ($isAjax) send_json(['success' => false, 'message' => mysqli_error($conn)]);
            die();
        }
        mysqli_stmt_bind_param($stmt, 'sss', $title, $description, $category);
        $ok = mysqli_stmt_execute($stmt);
        if ($isAjax) {
            if ($ok) send_json(['success' => true, 'message' => 'Note added.']);
            else send_json(['success' => false, 'message' => mysqli_stmt_error($stmt)]);
        }
        mysqli_stmt_close($stmt);
        if (!$isAjax) header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    if ($action === 'update') {
        $sql = "UPDATE notes SET title = ?, description = ?, category = ? WHERE sno = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            if ($isAjax) send_json(['success' => false, 'message' => mysqli_error($conn)]);
            die();
        }
        mysqli_stmt_bind_param($stmt, 'sssi', $title, $description, $category, $sno);
        $ok = mysqli_stmt_execute($stmt);
        if ($isAjax) {
            if ($ok) send_json(['success' => true, 'message' => 'Note updated.']);
            else send_json(['success' => false, 'message' => mysqli_stmt_error($stmt)]);
        }
        mysqli_stmt_close($stmt);
        if (!$isAjax) header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    if ($action === 'delete') {
        $sql = "DELETE FROM notes WHERE sno = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            if ($isAjax) send_json(['success' => false, 'message' => mysqli_error($conn)]);
            die();
        }
        mysqli_stmt_bind_param($stmt, 'i', $sno);
        $ok = mysqli_stmt_execute($stmt);
        if ($isAjax) {
            if ($ok) send_json(['success' => true, 'message' => 'Note deleted.']);
            else send_json(['success' => false, 'message' => mysqli_stmt_error($stmt)]);
        }
        mysqli_stmt_close($stmt);
        if (!$isAjax) header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Fetch notes (prepared statement example)
$notes = [];
$sql = "SELECT sno, title, description, category, tstamp FROM notes ORDER BY tstamp DESC";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $rsno, $rtitle, $rdescription, $rcategory, $rtstamp);
    while (mysqli_stmt_fetch($stmt)) {
        $notes[] = [
            'sno' => $rsno,
            'title' => $rtitle,
            'description' => $rdescription,
            'category' => $rcategory,
            'tstamp' => $rtstamp,
        ];
    }
    mysqli_stmt_close($stmt);
} else {
    // fallback: direct query
    $res = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($res)) $notes[] = $row;
}

// Utility: safe output
function e($str) { return htmlspecialchars($str ?? '', ENT_QUOTES); }

?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>iNotes — Modern Notes</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="//cdn.datatables.net/2.3.4/css/dataTables.dataTables.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet" />

    <style>
      /* Small UI enhancements */
      body { transition: background-color .25s, color .25s; }
      .note-card { transition: transform .15s ease, box-shadow .15s ease; }
      .note-card:hover { transform: translateY(-4px); box-shadow: 0 6px 18px rgba(0,0,0,.08); }
      .truncate-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
      /* Hide table on small screens; show cards instead */
      @media (max-width: 767.98px) {
        #notesTable_wrapper { display: none !important; }
        .cards-grid { display: grid; grid-template-columns: 1fr; gap: 1rem; }
      }
      @media (min-width: 768px) {
        .cards-grid { display: none; }
      }
      .category-badge { font-size: .75rem; padding: .35rem .6rem; border-radius: .5rem; color: #fff; }
      .char-counter { font-size: .8rem; color: #6c757d; }
      .rounded-input { border-radius: .6rem; }
    </style>
  </head>
  <body>

    <nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top shadow-sm">
      <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="#">
          <i class="bi bi-journal-text fs-4"></i>
          <span class="fw-bold">iNotes</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="nav">
          <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <li class="nav-item"><a class="nav-link active" href="#">Home</a></li>
            <li class="nav-item"><a class="nav-link" href="#">About</a></li>
          </ul>

          <div class="d-flex align-items-center gap-3">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="darkModeToggle">
              <label class="form-check-label" for="darkModeToggle">Dark</label>
            </div>
            <a class="btn btn-outline-primary d-none d-lg-inline" href="#">Sign in</a>
            <a class="btn btn-primary d-none d-lg-inline" href="#">Sign up</a>
          </div>
        </div>
      </div>
    </nav>

    <main class="container my-4">
      <div class="row g-3">
        <div class="col-12 col-lg-4">
          <div class="card p-3 rounded shadow-sm">
            <h5 class="mb-3">Add Note</h5>
            <form id="addNoteForm">
              <div class="mb-3">
                <label class="form-label">Title</label>
                <input id="title" name="title" class="form-control rounded-input" maxlength="120" required />
              </div>
              <div class="mb-2">
                <label class="form-label">Description</label>
                <textarea id="description" name="description" class="form-control rounded-input" rows="4" maxlength="1000" required></textarea>
                <div class="d-flex justify-content-between mt-1 align-items-center">
                  <small class="char-counter" id="descCounter">0 / 1000</small>
                  <small class="text-muted">Optional: Add useful details</small>
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label">Category / Tag</label>
                <input id="category" name="category" class="form-control rounded-input" placeholder="e.g. Work, Personal, Idea" maxlength="50" />
              </div>
              <div class="d-grid">
                <button type="submit" class="btn btn-primary">Add Note</button>
              </div>
            </form>
          </div>
        </div>

        <div class="col-12 col-lg-8">
          <div class="card p-3 rounded shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h5 class="mb-0">Your Notes</h5>
              <small class="text-muted">Responsive • Searchable • Paginated</small>
            </div>

            <!-- Desktop table (DataTable) -->
            <div class="table-responsive">
              <table id="notesTable" class="table table-hover align-middle">
                <thead>
                  <tr>
                    <th>Sno</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>Date</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($notes as $n): ?>
                    <tr data-sno="<?php echo e($n['sno']); ?>">
                      <td><?php echo e($n['sno']); ?></td>
                      <td><?php echo e($n['title']); ?></td>
                      <td class="truncate-2"><?php echo e($n['description']); ?></td>
                      <td>
                        <?php if ($n['category']): ?>
                          <span class="category-badge" style="background-color: <?php echo '#' . substr(md5($n['category']), 0, 6); ?>"><?php echo e($n['category']); ?></span>
                        <?php endif; ?>
                      </td>
                      <td><?php echo e($n['tstamp']); ?></td>
                      <td>
                        <button class="btn btn-sm btn-outline-primary edit rounded-pill" type="button" data-sno="<?php echo e($n['sno']); ?>"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger delete rounded-pill" type="button" data-sno="<?php echo e($n['sno']); ?>"><i class="bi bi-trash"></i></button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <!-- Mobile cards -->
            <div class="cards-grid mt-3">
              <?php foreach ($notes as $n): ?>
                <div class="note-card card p-3">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <h6 class="mb-1"><?php echo e($n['title']); ?></h6>
                      <p class="mb-1 text-muted truncate-2"><?php echo e($n['description']); ?></p>
                      <?php if ($n['category']): ?>
                        <span class="category-badge" style="background-color: <?php echo '#' . substr(md5($n['category']), 0, 6); ?>"><?php echo e($n['category']); ?></span>
                      <?php endif; ?>
                    </div>
                    <div class="d-flex flex-column gap-2 ms-3">
                      <small class="text-muted"><?php echo e($n['tstamp']); ?></small>
                      <div>
                        <button class="btn btn-sm btn-outline-primary edit me-1" data-sno="<?php echo e($n['sno']); ?>"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger delete" data-sno="<?php echo e($n['sno']); ?>"><i class="bi bi-trash"></i></button>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

          </div>
        </div>
      </div>
    </main>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <form id="editForm">
            <div class="modal-header">
              <h5 class="modal-title">Edit Note</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" id="editSno" name="sno">
              <div class="mb-3">
                <label class="form-label">Title</label>
                <input id="editTitle" name="title" class="form-control rounded-input" maxlength="120" required />
              </div>
              <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea id="editDescription" name="description" class="form-control rounded-input" rows="5" maxlength="1000" required></textarea>
                <div class="d-flex justify-content-between mt-1">
                  <small class="char-counter" id="editDescCounter">0 / 1000</small>
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label">Category / Tag</label>
                <input id="editCategory" name="category" class="form-control rounded-input" maxlength="50" />
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              <button type="submit" class="btn btn-primary">Save changes</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="//cdn.datatables.net/2.3.4/js/dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

    <script>
      // --------- Utilities ---------
      function qs(id){ return document.getElementById(id); }
      function hashColor(str){ // return light-contrasting color
        if(!str) return '#6c757d';
        let hash = 0; for (let i=0;i<str.length;i++) hash = str.charCodeAt(i) + ((hash<<5)-hash);
        const h = Math.abs(hash) % 360; return `hsl(${h} 70% 40%)`;
      }

      // --------- Dark mode (persisted) ---------
      (function(){
        const toggle = qs('darkModeToggle');
        const theme = localStorage.getItem('inotes_theme') || 'light';
        document.documentElement.setAttribute('data-bs-theme', theme === 'dark' ? 'dark' : 'light');
        toggle.checked = (theme === 'dark');
        toggle.addEventListener('change', function(){
          const t = this.checked ? 'dark' : 'light';
          document.documentElement.setAttribute('data-bs-theme', t);
          localStorage.setItem('inotes_theme', t);
        });
      })();

      // --------- DataTable init ---------
      $(document).ready(function(){
        $('#notesTable').DataTable({
          pageLength: 8,
          lengthChange: false,
          columnDefs: [{ orderable: false, targets: -1 }]
        });
      });

      // --------- Character counters ---------
      function bindCounter(textareaId, counterId){
        const ta = qs(textareaId); const c = qs(counterId);
        if(!ta || !c) return;
        const update = () => { c.textContent = ta.value.length + ' / ' + (ta.maxLength > 0 ? ta.maxLength : 1000); };
        ta.addEventListener('input', update); update();
      }
      bindCounter('description','descCounter');
      bindCounter('editDescription','editDescCounter');

      // --------- Add note (AJAX) ---------
      qs('addNoteForm').addEventListener('submit', function(e){
        e.preventDefault();
        const form = e.target;
        const fd = new FormData(form);
        fd.append('action','insert'); fd.append('ajax','1');
        fetch(location.href, { method: 'POST', body: fd })
          .then(r => r.json())
          .then(resp => {
            if(resp.success){
              Swal.fire({ icon: 'success', title: 'Added', text: resp.message, timer: 1200, showConfirmButton: false })
                .then(() => location.reload());
            } else {
              Swal.fire('Error', resp.message || 'Could not add note', 'error');
            }
          }).catch(err => Swal.fire('Error', String(err), 'error'));
      });

      // --------- Edit: open modal and fill values ---------
      function openEditModal(sno, title, description, category){
        qs('editSno').value = sno;
        qs('editTitle').value = title;
        qs('editDescription').value = description;
        qs('editCategory').value = category;
        const modal = new bootstrap.Modal(qs('editModal'));
        modal.show();
      }

      // Attach event listeners to dynamic edit buttons
      function attachRowHandlers(){
        // Fix: getElementsByTagName (plural) used where needed and closest('tr')
        document.querySelectorAll('.edit').forEach(btn => {
          btn.addEventListener('click', function(e){
            const tr = e.target.closest('tr') || e.target.closest('.note-card');
            if(!tr) return;
            const sno = btn.getAttribute('data-sno') || tr.getAttribute('data-sno');
            // find cells if row
            let title = '', description = '', category = '';
            if(tr.tagName.toLowerCase() === 'tr'){
              const tds = tr.getElementsByTagName('td');
              title = tds[0] ? tds[0].innerText.trim() : '';
              description = tds[1] ? tds[1].innerText.trim() : '';
              const catEl = tds[2] ? tds[2].querySelector('.category-badge') : null;
              category = catEl ? catEl.innerText.trim() : '';
            } else {
              // card
              title = tr.querySelector('h6') ? tr.querySelector('h6').innerText : '';
              description = tr.querySelector('p') ? tr.querySelector('p').innerText : '';
              category = tr.querySelector('.category-badge') ? tr.querySelector('.category-badge').innerText : '';
            }
            openEditModal(sno, title, description, category);
          });
        });

        document.querySelectorAll('.delete').forEach(btn => {
          btn.addEventListener('click', function(e){
            const sno = btn.getAttribute('data-sno');
            Swal.fire({
              title: 'Delete this note?',
              text: 'This action cannot be undone.',
              icon: 'warning',
              showCancelButton: true,
              confirmButtonText: 'Yes, delete',
            }).then(result => {
              if(result.isConfirmed){
                const fd = new FormData(); fd.append('action','delete'); fd.append('sno', sno); fd.append('ajax','1');
                fetch(location.href, { method: 'POST', body: fd })
                  .then(r => r.json())
                  .then(resp => {
                    if(resp.success){
                      Swal.fire('Deleted', resp.message, 'success').then(() => location.reload());
                    } else Swal.fire('Error', resp.message || 'Delete failed', 'error');
                  }).catch(err => Swal.fire('Error', String(err), 'error'));
              }
            });
          });
        });
      }

      // Bind handlers now
      attachRowHandlers();

      // --------- Edit form submit (AJAX) ---------
      qs('editForm').addEventListener('submit', function(e){
        e.preventDefault();
        const fd = new FormData(this); fd.append('action','update'); fd.append('ajax','1');
        fetch(location.href, { method: 'POST', body: fd })
          .then(r => r.json())
          .then(resp => {
            if(resp.success){
              Swal.fire({ icon: 'success', title: 'Updated', text: resp.message, timer: 1000, showConfirmButton: false })
                .then(() => location.reload());
            } else Swal.fire('Error', resp.message || 'Update failed', 'error');
          }).catch(err => Swal.fire('Error', String(err), 'error'));
      });

    </script>
  </body>
</html>