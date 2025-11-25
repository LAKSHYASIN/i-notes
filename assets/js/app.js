// assets/js/app.js
// JS separated from index.php. Keeps app logic: datatable init, dark mode, AJAX handlers, counters, attach buttons.

(function () {
  function qs(id){ return document.getElementById(id); }

  // Dark mode
  (function(){
    const toggle = qs('darkModeToggle');
    const theme = localStorage.getItem('inotes_theme') || 'light';
    document.documentElement.setAttribute('data-bs-theme', theme === 'dark' ? 'dark' : 'light');
    if(toggle) toggle.checked = (theme === 'dark');
    if(toggle) toggle.addEventListener('change', function(){
      const t = this.checked ? 'dark' : 'light';
      document.documentElement.setAttribute('data-bs-theme', t);
      localStorage.setItem('inotes_theme', t);
    });
  })();

  // DataTable
  $(document).ready(function(){
    if($('#notesTable').length) {
      $('#notesTable').DataTable({ pageLength: 8, lengthChange: false, columnDefs: [{ orderable: false, targets: -1 }] });
    }
  });

  // counters
  function bindCounter(textareaId, counterId){
    const ta = qs(textareaId); const c = qs(counterId);
    if(!ta || !c) return;
    const update = () => { c.textContent = ta.value.length + ' / ' + (ta.maxLength > 0 ? ta.maxLength : 1000); };
    ta.addEventListener('input', update); update();
  }
  bindCounter('description','descCounter');
  bindCounter('editDescription','editDescCounter');

  // Add note AJAX
  const addForm = qs('addNoteForm');
  if(addForm){
    addForm.addEventListener('submit', function(e){
      e.preventDefault();
      const fd = new FormData(this); fd.append('action','insert'); fd.append('ajax','1');
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
  }

  // Edit modal
  function openEditModal(sno, title, description, category){
    if(qs('editSno')) qs('editSno').value = sno;
    if(qs('editTitle')) qs('editTitle').value = title;
    if(qs('editDescription')) qs('editDescription').value = description;
    if(qs('editCategory')) qs('editCategory').value = category;
    const modalEl = qs('editModal');
    if(modalEl) new bootstrap.Modal(modalEl).show();
  }

  function attachRowHandlers(){
    document.querySelectorAll('.edit').forEach(btn => {
      btn.addEventListener('click', function(e){
        const tr = e.target.closest('tr') || e.target.closest('.note-card');
        if(!tr) return;
        const sno = btn.getAttribute('data-sno') || tr.getAttribute('data-sno');
        let title = '', description = '', category = '';
        if(tr.tagName.toLowerCase() === 'tr'){
          const tds = tr.getElementsByTagName('td');
          title = tds[0] ? tds[0].innerText.trim() : '';
          description = tds[1] ? tds[1].innerText.trim() : '';
          const catEl = tds[2] ? tds[2].querySelector('.category-badge') : null;
          category = catEl ? catEl.innerText.trim() : '';
        } else {
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
        Swal.fire({ title: 'Delete this note?', text: 'This action cannot be undone.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, delete' }).then(result => {
          if(result.isConfirmed){
            const fd = new FormData(); fd.append('action','delete'); fd.append('sno', sno); fd.append('ajax','1');
            fetch(location.href, { method: 'POST', body: fd })
              .then(r => r.json())
              .then(resp => {
                if(resp.success){ Swal.fire('Deleted', resp.message, 'success').then(() => location.reload()); }
                else Swal.fire('Error', resp.message || 'Delete failed', 'error');
              }).catch(err => Swal.fire('Error', String(err), 'error'));
          }
        });
      });
    });
  }

  attachRowHandlers();

  // Edit submit
  const editForm = qs('editForm');
  if(editForm){
    editForm.addEventListener('submit', function(e){
      e.preventDefault();
      const fd = new FormData(this); fd.append('action','update'); fd.append('ajax','1');
      fetch(location.href, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(resp => {
          if(resp.success){ Swal.fire({ icon: 'success', title: 'Updated', text: resp.message, timer: 1000, showConfirmButton: false }).then(() => location.reload()); }
          else Swal.fire('Error', resp.message || 'Update failed', 'error');
        }).catch(err => Swal.fire('Error', String(err), 'error'));
    });
  }

})();
