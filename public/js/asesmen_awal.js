// --- Fungsi Autocomplete untuk Ruang Saja ---
function setupRuangAutocomplete() {
    const inputEl = document.getElementById('ruang_input');
    const listEl = document.getElementById('ruang-list');

    // Pastikan elemen ada di halaman
    if (!inputEl || !listEl) return;

    // Fungsi untuk menyembunyikan daftar
    const hideList = () => {
        listEl.style.display = 'none';
        listEl.innerHTML = '';
    };

    // Fungsi untuk menampilkan daftar
    const showList = () => {
        if (listEl.innerHTML) {
            listEl.style.display = 'block';
        }
    };

    // Fungsi debounce untuk menunda pencarian
    function debounce(fn, delay = 300) {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), delay);
        };
    }

    // Event listener untuk input
    inputEl.addEventListener('input', debounce(async () => {
        const q = inputEl.value.trim();
        if (q.length < 2) {
            hideList();
            return;
        }

        try {
            const response = await fetch(`../actions/search_ruang_list.php?q=${encodeURIComponent(q)}`);
            if (!response.ok) throw new Error('Network response was not ok');

            const data = await response.json();

            listEl.innerHTML = ''; // Kosongkan daftar sebelumnya
            if (data.length > 0) {
                data.forEach(item => {
                    const li = document.createElement('li');
                    li.classList.add('list-group-item', 'list-group-item-action', 'py-1');
                    li.textContent = item;
                    listEl.appendChild(li);
                });
                showList();
            } else {
                hideList();
            }
        } catch (error) {
            console.error('Fetch error:', error);
            hideList();
        }
    }));

    // Event listener untuk klik pada saran
    listEl.addEventListener('click', e => {
        if (e.target.tagName === 'LI') {
            inputEl.value = e.target.textContent;
            hideList();
        }
    });

    // Sembunyikan daftar jika klik di luar input atau daftar
    document.addEventListener('click', e => {
        if (e.target !== inputEl && !listEl.contains(e.target)) {
            hideList();
        }
    });
}

// Panggil fungsi saat dokumen selesai dimuat
document.addEventListener('DOMContentLoaded', setupRuangAutocomplete);