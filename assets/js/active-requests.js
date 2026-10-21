(() => {
  function init(root) {
    if (!root) return;
    const checkboxes = [...root.querySelectorAll('[data-service-check]')];
    const hardwareId = root.dataset.hardwareId;
    const section = root.querySelector('[data-hardware-section]');
    const list = root.querySelector('[data-hardware-list]');
    const add = root.querySelector('[data-add-hardware]');
    const summary = root.querySelector('[data-service-summary]');
    const template = document.getElementById('hardware-row-template');
    let counter = 0;
    function update() {
      const checked = checkboxes.filter(x => x.checked);
      section.hidden = !checked.some(x => x.value === hardwareId);
      section.querySelectorAll('input,select,textarea,button').forEach(x => x.disabled = section.hidden);
      summary.textContent = checked.length ? checked.map(x => x.dataset.label).join(', ') : 'Pilih jenis perkhidmatan';
      if (!section.hidden && !list.children.length) addRow();
    }
    function addRow(data = {}) {
      if (list.children.length >= 20) return;
      const node = template.content.firstElementChild.cloneNode(true);
      node.querySelectorAll('[data-field]').forEach(input => {
        const key = input.dataset.field;
        input.name = `hardware[${counter}][${key}]`;
        input.value = data[key] ?? (key === 'work_type' ? 'replacement' : '');
      });
      counter++;
      const work = node.querySelector('[data-field="work_type"]');
      const old = node.querySelector('[data-old-fields]');
      const toggle = () => { old.hidden = work.value === 'installation'; old.querySelectorAll('input').forEach(x => x.disabled = old.hidden); };
      work.addEventListener('change', toggle);
      node.querySelector('[data-remove-hardware]').addEventListener('click', () => { node.remove(); if (!list.children.length && !section.hidden) addRow(); });
      list.append(node); toggle();
      node.querySelectorAll('input,select,textarea').forEach(x => x.disabled = section.hidden || (old.hidden && old.contains(x)));
    }
    checkboxes.forEach(x => x.addEventListener('change', update));
    add.addEventListener('click', () => addRow());
    root.multiSet = (ids,hardware) => {
      checkboxes.forEach(x => x.checked = ids.map(String).includes(x.value));
      list.replaceChildren(); counter = 0; update();
      if (!section.hidden) { list.replaceChildren(); (hardware?.length ? hardware : [{}]).forEach(addRow); }
    };
    update();
  }
  document.querySelectorAll('[data-service-multi]').forEach(init);
})();

(() => {
 function update(form){const owner=form.querySelector('[data-ownership]'),type=form.querySelector('[data-device-type]'),other=form.querySelector('[data-other-device]'),asset=form.querySelector('[data-asset-field]'),extra=form.querySelector('[data-accessory-other]'),box=form.querySelector('[data-other-accessory]');if(!owner)return;const isOther=type.value==='Other';other.hidden=!isOther;other.querySelector('input').required=isOther;asset.hidden=owner.value==='personal';asset.querySelector('input').disabled=owner.value==='personal';extra.hidden=!box.checked;extra.disabled=!box.checked;}
 function setup(form){if(form.dataset.deviceReady)return;form.dataset.deviceReady='1';form.addEventListener('change',()=>update(form));update(form);}
 window.setDeviceForm=(form,data={})=>{if(!form)return;setup(form);for(const [k,v] of Object.entries(data)){if(k==='accessories')continue;const field=Array.from(form.elements).find(el=>el.name===k);if(field&&typeof v==='string')field.value=v;}const acc=Array.isArray(data.accessories)?data.accessories:[];form.querySelectorAll('input[name="accessories[]"]').forEach(el=>el.checked=acc.includes(el.value));const known=['Charger','Mouse','Beg Laptop'];const other=acc.find(v=>!known.includes(v));if(other){form.querySelector('[data-other-accessory]').checked=true;form.querySelector('[data-accessory-other]').value=other==='Lain-lain'?'':other;}update(form);};
 document.addEventListener('DOMContentLoaded',()=>document.querySelectorAll('form').forEach(f=>{if(f.querySelector('[data-ownership]'))setup(f);}));
})();
(() => {
 const records=JSON.parse(document.getElementById('active-records-json')?.textContent||'{}');
 const detail=document.getElementById('details-dialog'), edit=document.getElementById('edit-dialog'), ready=document.getElementById('ready-dialog');
 document.querySelectorAll('.dialog-close').forEach(b=>b.addEventListener('click',()=>b.closest('dialog').close()));
 document.querySelectorAll('.details-trigger').forEach(b=>b.addEventListener('click',()=>{const tpl=document.getElementById('active-data-'+b.dataset.id);if(!tpl)return;document.getElementById('details-body').replaceChildren(tpl.content.cloneNode(true));detail.showModal();}));
 document.querySelectorAll('.edit-trigger').forEach(b=>b.addEventListener('click',()=>{const r=records[b.dataset.id];if(!r)return;const set=(id,v)=>document.getElementById(id).value=v??'';
 set('edit-id',r.id);set('edit-name',r.requester_name);set('edit-department',r.department_id);set('edit-details',r.service_details);set('edit-cost',r.estimated_cost);set('edit-date',r.requested_at.replace(' ','T').slice(0,16));set('edit-handler',r.handler_id);
 edit.querySelector('[data-service-multi]').multiSet(r.service_type_ids,r.hardware);if(window.setDeviceForm)window.setDeviceForm(edit.querySelector('form'),r);edit.showModal();}));
 document.querySelectorAll('.ready-trigger').forEach(b=>b.addEventListener('click',()=>{document.getElementById('ready-id').value=b.dataset.id;document.getElementById('ready-uid').textContent=b.dataset.uid;ready.showModal();}));
 [detail,edit,ready].forEach(d=>d.addEventListener('click',e=>{if(e.target===d)d.close();}));
})();
