<x-filament-panels::page>
    <div x-data="scheduleCalendar()" class="w-full">
        <div class="mb-4 flex items-center gap-3">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Outlet:</label>
            <select x-model="filterMerchant" @change="reloadEvents()"
                class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-w-[200px]">
                <option value="">Semua Outlet</option>
                @foreach(\App\Models\Merchants\Merchant::query()->where('type', \App\Enums\Merchants\MerchantType::Merchant)->orderBy('name')->get() as $m)
                    <option value="{{ $m->id }}">{{ $m->name }}</option>
                @endforeach
            </select>
        </div>
        <div id="schedule-calendar" class="bg-white dark:bg-gray-900 rounded-xl shadow-sm p-4" style="min-height: 600px;"></div>
    </div>

    <template id="event-detail-template">
        <div class="text-sm space-y-3">
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <span class="text-xs text-gray-400 dark:text-gray-500">Outlet</span>
                    <p class="font-medium text-gray-700 dark:text-gray-300" data-field="merchant_name"></p>
                </div>
                <div>
                    <span class="text-xs text-gray-400 dark:text-gray-500">Jam</span>
                    <p class="font-medium text-gray-700 dark:text-gray-300" data-field="time_range"></p>
                </div>
            </div>
            <div class="pt-2 border-t border-gray-100 dark:border-gray-700">
                <span class="text-xs text-gray-400 dark:text-gray-500">Karyawan</span>
                <div data-field="users_list" class="mt-1 space-y-1"></div>
            </div>
        </div>
    </template>

    <template id="user-item-template">
        <div class="flex items-center justify-between text-sm py-0.5">
            <span class="text-gray-700 dark:text-gray-300" data-field="uname"></span>
            <span class="text-gray-400 dark:text-gray-500 text-xs" data-field="unotes"></span>
        </div>
    </template>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <script>
        function scheduleCalendar() {
            return {
                calendar: null,
                filterMerchant: '',

                init() {
                    const el = document.getElementById('schedule-calendar');
                    const modalEl = document.createElement('div');
                    modalEl.id = 'fc-event-modal';
                    modalEl.innerHTML = `
                        <div class="fixed inset-0 z-50 flex items-center justify-center hidden" id="fc-modal-overlay">
                            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" id="fc-modal-backdrop"></div>
                            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-sm w-full mx-4 p-6 z-10">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4" id="fc-modal-title">Detail Jadwal</h3>
                                <div id="fc-modal-body"></div>
                                <button class="mt-5 w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors" id="fc-modal-close">Tutup</button>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(modalEl);

                    const closeModal = () => document.getElementById('fc-modal-overlay').classList.add('hidden');
                    document.getElementById('fc-modal-backdrop').addEventListener('click', closeModal);
                    document.getElementById('fc-modal-close').addEventListener('click', closeModal);

                    const self = this;
                    this.calendar = new FullCalendar.Calendar(el, {
                        initialView: 'dayGridMonth',
                        height: 'auto',
                        firstDay: 1,
                        locale: 'id',
                        headerToolbar: {
                            left: 'prev,next today',
                            center: 'title',
                            right: 'dayGridMonth,timeGridWeek,timeGridDay'
                        },
                        buttonText: { today: 'Hari Ini', month: 'Bulan', week: 'Minggu', day: 'Hari' },
                        allDaySlot: false,
                        slotMinTime: '05:00:00',
                        slotMaxTime: '24:00:00',
                        slotDuration: '01:00:00',
                        eventDisplay: 'block',
                        events: function(info, successCallback, failureCallback) {
                            const params = new URLSearchParams({
                                start: info.startStr,
                                end: info.endStr,
                                view: self.calendar ? self.calendar.view.type : 'dayGridMonth',
                            });
                            if (self.filterMerchant) {
                                params.set('merchant_id', self.filterMerchant);
                            }
                            fetch('{{ route('api.schedule-calendar') }}?' + params.toString())
                                .then(r => r.json())
                                .then(successCallback)
                                .catch(failureCallback);
                        },
                        eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
                        eventClick: (info) => {
                            const p = info.event.extendedProps;
                            const tpl = document.getElementById('event-detail-template').content.cloneNode(true);
                            tpl.querySelector('[data-field="merchant_name"]').textContent = p.merchant_name;
                            tpl.querySelector('[data-field="time_range"]').textContent = p.start_time + ' - ' + p.end_time;

                            const listEl = tpl.querySelector('[data-field="users_list"]');
                            if (p.users) {
                                p.users.forEach(u => {
                                    const item = document.getElementById('user-item-template').content.cloneNode(true);
                                    item.querySelector('[data-field="uname"]').textContent = u.name;
                                    item.querySelector('[data-field="unotes"]').textContent = u.notes ? u.notes : '';
                                    listEl.appendChild(item);
                                });
                            }

                            document.getElementById('fc-modal-body').innerHTML = '';
                            document.getElementById('fc-modal-body').appendChild(tpl);
                            document.getElementById('fc-modal-overlay').classList.remove('hidden');
                        },
                        eventDidMount: (info) => {
                            info.el.style.cursor = 'pointer';
                            info.el.style.borderRadius = '6px';
                            info.el.style.padding = '2px 6px';
                            info.el.style.fontSize = '0.75rem';
                            info.el.style.fontWeight = '500';
                        },
                        noEventsText: 'Tidak ada jadwal shift',
                    });
                    this.calendar.render();
                },

                reloadEvents() {
                    if (this.calendar) {
                        this.calendar.refetchEvents();
                    }
                },

                destroy() {
                    if (this.calendar) { this.calendar.destroy(); }
                    const modal = document.getElementById('fc-event-modal');
                    if (modal) { modal.remove(); }
                }
            };
        }
    </script>
    <style>
        .fc { font-size: 0.875rem; }
        .fc .fc-toolbar-title { font-size: 1.125rem; font-weight: 600; }
        .fc .fc-button { font-size: 0.8125rem; padding: 0.375rem 0.75rem; border-radius: 0.375rem; font-weight: 500; }
        .fc .fc-button-primary { background-color: #3B82F6; border-color: #3B82F6; }
        .fc .fc-button-primary:not(:disabled):hover { background-color: #2563EB; border-color: #2563EB; }
        .fc .fc-button-primary:not(:disabled).fc-button-active { background-color: #1D4ED8; border-color: #1D4ED8; }
        .fc .fc-timegrid-slot { height: 3rem; }
        .fc-theme-standard td, .fc-theme-standard th { border-color: #E5E7EB; }
        .dark .fc-theme-standard td, .dark .fc-theme-standard th { border-color: #374151; }
        .dark .fc { color: #D1D5DB; }
        .dark .fc .fc-daygrid-day-number { color: #D1D5DB; }
        .dark .fc .fc-col-header-cell-cushion { color: #D1D5DB; }
    </style>
</x-filament-panels::page>
