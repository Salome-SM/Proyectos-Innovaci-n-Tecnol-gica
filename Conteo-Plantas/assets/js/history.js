// assets/js/history.js
class HistoryManager {
    constructor() {
        this.initializeFilters();
        this.loadHistory();
    }

    initializeFilters() {
        const filters = ['block', 'bed', 'variety', 'date'];
        filters.forEach(filter => {
            const element = document.getElementById(`filter_${filter}`);
            if (element) {
                element.addEventListener('change', () => this.loadHistory());
            }
        });
    }

    async loadHistory() {
        try {
            const filters = {
                block: document.getElementById('filter_block')?.value,
                bed: document.getElementById('filter_bed')?.value,
                variety: document.getElementById('filter_variety')?.value,
                date: document.getElementById('filter_date')?.value
            };

            const response = await fetch('/plant_counter/api/history.php?' + new URLSearchParams(filters));
            const data = await response.json();

            if (data.success) {
                this.renderHistory(data.records);
            } else {
                console.error('Error loading history:', data.message);
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }

    renderHistory(records) {
        const tbody = document.getElementById('historyTable');
        tbody.innerHTML = '';

        records.forEach(record => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${record.count_date}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${record.block_number}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${record.bed_number}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${record.variety}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${record.total_plants}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <a href="${record.processed_video_path}" class="text-green-600 hover:text-green-900" target="_blank">Ver Video</a>
                </td>
            `;
            tbody.appendChild(row);
        });
    }
}

new HistoryManager();