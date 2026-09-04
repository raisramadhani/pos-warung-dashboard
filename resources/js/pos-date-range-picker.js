/**
 * POS Date Range Picker wrapper.
 *
 * Wraps the vendored vanilla-datetimerange-picker (MIT, alumuko) and exposes a
 * small global factory (`window.PosDateRangePicker`) so the POS Blade pages can
 * create a Tailwind-styled single-calendar date range picker without reloading
 * the page (Bluetooth printer constraint: no full page refreshes).
 *
 * Styling lives in `resources/css/pos.css` under the `.daterangepicker` scope.
 */

import moment from 'moment';
import DateRangePicker from './vendor/vanilla-datetimerange-picker/vanilla-datetimerange-picker.js';

const DEFAULT_OPTIONS = {
    autoUpdateInput: false,
    alwaysShowCalendars: true,
    linkedCalendars: true,
    showDropdowns: true,
    opens: 'right',
    drops: 'down',
    locale: {
        format: 'DD/MM/YYYY',
        separator: ' - ',
        applyLabel: 'Terapkan',
        cancelLabel: 'Batal',
        customRangeLabel: 'Pilih Tanggal',
        daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
        monthNames: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
        firstDay: 1,
    },
    ranges: {
        'Hari Ini': [moment().startOf('day'), moment().endOf('day')],
        '7 Hari Terakhir': [moment().subtract(6, 'days').startOf('day'), moment().endOf('day')],
        '30 Hari Terakhir': [moment().subtract(29, 'days').startOf('day'), moment().endOf('day')],
        'Bulan Ini': [moment().startOf('month'), moment().endOf('month')],
        'Bulan Lalu': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
    },
};

class PosDateRangePicker {
    /**
     * @param {string|HTMLElement} element - input element (id or HTMLElement) to bind.
     * @param {object} [options] - overrides merged over DEFAULT_OPTIONS.
     */
    constructor(element, options = {}) {
        this.element = typeof element === 'string' ? document.getElementById(element) : element;
        if (!this.element) {
            throw new Error(`PosDateRangePicker: element "${element}" tidak ditemukan.`);
        }

        this.options = {
            ...DEFAULT_OPTIONS,
            ...options,
            locale: {
                ...DEFAULT_OPTIONS.locale,
                ...(options.locale || {}),
            },
        };

        this._picker = new DateRangePicker(this.element, this.options, (start, end, label) => {
            // Callback dipanggil oleh library saat rentang berubah & picker ditutup
            // (via tombol Apply, klik preset range, atau hide dengan perubahan).
            this._range = {
                start: start.clone(),
                end: end.clone(),
                label: label || null,
            };
            this.element.value = `${start.format('DD/MM/YYYY')}${this.options.locale.separator}${end.format('DD/MM/YYYY')}`;
            if (typeof this.onApply === 'function') {
                this.onApply(this._range);
            }
        });
    }

    get range() {
        return this._range || null;
    }

    /**
     * Set rentang tanggal programmatically.
     * @param {string} startDate - 'YYYY-MM-DD' atau format locale.
     * @param {string} endDate
     */
    setRange(startDate, endDate) {
        const start = moment(startDate, 'YYYY-MM-DD');
        const end = moment(endDate, 'YYYY-MM-DD');
        if (!start.isValid() || !end.isValid()) {
            this._picker.setStartDate(moment());
            this._picker.setEndDate(moment());
            this.clearInput();
            return;
        }
        this._picker.setStartDate(start);
        this._picker.setEndDate(end);
        this._range = {
            start: start.startOf('day'),
            end: end.endOf('day'),
            label: null,
        };
        this.element.value = `${start.format('DD/MM/YYYY')}${this.options.locale.separator}${end.format('DD/MM/YYYY')}`;
    }

    clear() {
        this._range = null;
        this.clearInput();
        this._picker.setStartDate(moment().startOf('day'));
        this._picker.setEndDate(moment().endOf('day'));
    }

    clearInput() {
        this.element.value = '';
    }

    setValueLabel(label) {
        this.element.value = label;
    }

    setPlaceholder(placeholder) {
        this.element.placeholder = placeholder || '';
    }

    close() {
        if (this._picker && this._picker.isShowing) {
            this._picker.hide();
        }
    }
}

window.PosDateRangePicker = PosDateRangePicker;

// Ekspos moment global (sama seperti setup CDN asli) agar script inline Blade
// di halaman POS bisa memakai moment tanpa import.
window.moment = moment;

export default PosDateRangePicker;
