const detailsDialog = document.getElementById('request-details');

if (detailsDialog) {
    const labels = {
        in_progress: 'In Progress',
        ready: 'Ready',
        completed: 'Completed'
    };
    document.querySelectorAll('[data-details]').forEach((button) => {
        button.addEventListener('click', () => {
            const record = JSON.parse(button.dataset.record);
            detailsDialog.querySelectorAll('[data-field]').forEach((field) => {
                const key = field.dataset.field;
                let value = record[key];
                if (key === 'status') value = labels[value] || value;
                if (key === 'estimated_cost') {
                    value = 'RM ' + Number(value).toLocaleString('en-MY', {
                        minimumFractionDigits: 2, maximumFractionDigits: 2
                    });
                }
                if (key === 'requested_at') {
                    value = new Intl.DateTimeFormat('ms-MY', {
                        day: '2-digit', month: 'short', year: 'numeric',
                        hour: '2-digit', minute: '2-digit'
                    }).format(new Date(value.replace(' ', 'T')));
                }
                field.textContent = value || '—';
            });
            detailsDialog.showModal();
        });
    });
    detailsDialog.querySelector('.details-close').addEventListener('click', () => detailsDialog.close());
    detailsDialog.addEventListener('click', (event) => {
        const bounds = detailsDialog.getBoundingClientRect();
        if (event.target === detailsDialog &&
            (event.clientX < bounds.left || event.clientX > bounds.right ||
             event.clientY < bounds.top || event.clientY > bounds.bottom)) {
            detailsDialog.close();
        }
    });
}
