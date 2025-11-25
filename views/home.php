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

<!-- Edit Modal (kept in view) -->
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
