document.getElementById('ruang_dropdown').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const kelas = selectedOption ? selectedOption.getAttribute('data-kelas') : '';
    const kelasDropdown = document.getElementById('kelas_dropdown');

    // Mapping kelas dari tabel kamar ke opsi dropdown
    const kelasMapping = {
        'Kelas 3': 'III',
        'Kelas 2': 'II',
        'Kelas 1': 'I',
        'VIP': 'VIP'
    };

    if (kelas && kelasMapping[kelas]) {
        kelasDropdown.value = kelasMapping[kelas]; // Isi otomatis kolom Kelas
    } else {
        kelasDropdown.value = ''; // Reset jika tidak ada kelas valid
    }
});