const historyDialog = document.getElementById('history-details');

if (historyDialog) {
    const dateTimeFields = [
        'requested_at',
        'completed_at',
        'picked_up_at'
    ];

    function formatHistoryDate(value, includeTime) {
        if (!value) return '—';

        const date = new Date(
            value.includes(' ')
                ? value.replace(' ', 'T')
                : value + 'T00:00:00'
        );

        if (Number.isNaN(date.getTime())) return value;

        const options = {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        };

        if (includeTime) {
            options.hour = '2-digit';
            options.minute = '2-digit';
        }

        return new Intl.DateTimeFormat('ms-MY', options).format(date);
    }

    document.querySelectorAll('[data-history-details]').forEach((button) => {
        button.addEventListener('click', () => {
            const record = JSON.parse(button.dataset.record);

            historyDialog.querySelectorAll('[data-field]').forEach((field) => {
                const key = field.dataset.field;
                let value = record[key];

                if (key === 'estimated_cost') {
                    value = 'RM ' + Number(value).toLocaleString('en-MY', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                } else if (dateTimeFields.includes(key)) {
                    value = formatHistoryDate(value, true);
                } else if (key === 'payment_date') {
                    value = formatHistoryDate(value, false);
                }

                field.textContent =
                    value === null || value === undefined || value === ''
                        ? '—'
                        : value;
            });

            historyDialog.showModal();
        });
    });

    historyDialog.querySelector('.details-close')
        .addEventListener('click', () => {
            historyDialog.close();
        });

    historyDialog.addEventListener('click', (event) => {
        const bounds = historyDialog.getBoundingClientRect();

        if (
            event.target === historyDialog &&
            (
                event.clientX < bounds.left ||
                event.clientX > bounds.right ||
                event.clientY < bounds.top ||
                event.clientY > bounds.bottom
            )
        ) {
            historyDialog.close();
        }
    });
}