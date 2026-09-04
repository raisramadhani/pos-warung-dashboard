/**
 * Format angka rupiah kompak untuk sumbu/tooltip grafik ApexCharts.
 * Rp1.500 → "Rp1,5K"; Rp60.000 → "Rp60K"; Rp2.400.000 → "Rp2,4Jt".
 */
window.formatChartRupiah = function (val) {
    if (typeof val !== 'number' || Number.isNaN(val)) {
        return 'Rp0';
    }

    if (val >= 1000000) {
        const juta = val / 1000000;
        const formatted = Number.isInteger(juta)
            ? juta.toString()
            : juta.toFixed(1).replace(/\.0$/, '').replace('.', ',');
        return 'Rp' + formatted + 'Jt';
    }

    if (val >= 1000) {
        return 'Rp' + Math.round(val / 1000) + 'K';
    }

    return 'Rp' + Math.round(val);
};
