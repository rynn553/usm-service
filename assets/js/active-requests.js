(() => {
  const records = JSON.parse(document.getElementById('active-records-json')?.textContent || '{}');
  const detail = document.getElementById('details-dialog');
  const edit = document.getElementById('edit-dialog');
  const ready = document.getElementById('ready-dialog');
  document.querySelectorAll('.dialog-close').forEach(btn => btn.addEventListener('click', () => btn.closest('dialog').close()));
  document.querySelectorAll('.details-trigger').forEach(btn => btn.addEventListener('click', () => {
    const tpl = document.getElementById('active-data-' + btn.dataset.id);
    if (!tpl) return;
    document.getElementById('details-body').replaceChildren(tpl.content.cloneNode(true));
    detail.showModal();
  }));
  document.querySelectorAll('.edit-trigger').forEach(btn => btn.addEventListener('click', () => {
    const r = records[btn.dataset.id]; if (!r) return;
    const set = (id,value) => { document.getElementById(id).value = value ?? ''; };
    set('edit-id',r.id); set('edit-name',r.requester_name); set('edit-department',r.department_id);
    set('edit-type',r.service_type_id); set('edit-details',r.service_details);
    set('edit-cost',r.estimated_cost); set('edit-date',r.requested_at.replace(' ','T').slice(0,16));
    set('edit-handler',r.handler_id); edit.showModal();
  }));
  document.querySelectorAll('.ready-trigger').forEach(btn => btn.addEventListener('click', () => {
    document.getElementById('ready-id').value=btn.dataset.id;
    document.getElementById('ready-uid').textContent=btn.dataset.uid;
    ready.showModal();
  }));
  [detail,edit,ready].forEach(dialog => dialog.addEventListener('click', e => {
    if (e.target === dialog) dialog.close();
  }));
})();
