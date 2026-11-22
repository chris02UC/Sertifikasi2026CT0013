/**
 * "Sewa Garasi Baru" (new rental) form.
 * - Shows fields based on the vehicle type (plate, battery capacity and charge left).
 * - Limits the garage-type options to those allowed for the vehicle.
 * - Calculates the end date and the daily-rental day limit.
 * - Calls the server quote endpoint for available garages + cost breakdown.
 * The official cost calculation stays on the server (PricingService); JS only displays it.
 */
const form = document.getElementById('rental-form');

if (form) {
    const $ = (id) => document.getElementById(id);
    const allowedGarages = JSON.parse(form.dataset.allowed);
    const rupiah = (n) => 'Rp ' + Number(n).toLocaleString('id-ID');
    const toDate = (s) => { const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d); };
    const daysInMonth = (date) => new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();

    const fields = ['vehicle_type', 'plate_number', 'brand_model', 'battery_kwh', 'current_kwh', 'rental_type', 'days', 'start_date', 'garage_type'];

    function setVisible(selector, visible) {
        document.querySelectorAll(selector).forEach((el) => {
            el.classList.toggle('hidden', !visible);
            el.querySelectorAll('input, select').forEach((input) => { input.disabled = !visible; });
        });
    }

    function refreshVehicleFields() {
        const type = $('vehicle_type').value;
        // Plate number for every vehicle except bicycles; battery capacity only for electric cars.
        setVisible('[data-show-for="plate"]', type !== '' && type !== 'bicycle');
        setVisible('[data-show-for="battery"]', type === 'electric_car');

        const allowed = allowedGarages[type] ?? [];
        const garageSelect = $('garage_type');
        [...garageSelect.options].forEach((opt) => { opt.disabled = allowed.length > 0 && !allowed.includes(opt.value); });
        if (allowed.length > 0 && !allowed.includes(garageSelect.value)) {
            garageSelect.value = allowed[0];
        }
    }

    function refreshPeriod() {
        const daily = $('rental_type').value === 'daily';
        setVisible('[data-show-for="daily"]', daily);
        if (!$('start_date').value) return;

        const start = toDate($('start_date').value);
        const maxDays = daysInMonth(start) - 1;
        $('max-days').textContent = maxDays;
        $('days').max = maxDays;

        const duration = daily ? Number($('days').value || 0) : daysInMonth(start);
        if (duration >= 1) {
            const end = new Date(start);
            end.setDate(end.getDate() + duration - 1);
            $('end_date').value = end.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
        } else {
            $('end_date').value = '';
        }
    }

    function resetQuote(message) {
        $('garage-list').innerHTML = '';
        $('cost-summary').classList.add('hidden');
        $('submit-btn').disabled = true;
        $('quote-message').textContent = message ?? 'Data berubah. Klik "Cek Garasi Tersedia" lagi.';
    }

    function showCost(garage) {
        $('cost-rent').textContent = rupiah(garage.rent_cost);
        $('cost-charging').textContent = rupiah(garage.charging_cost);
        $('cost-total').textContent = rupiah(garage.total_cost);
        $('cost-summary').classList.remove('hidden');
        $('submit-btn').disabled = false;
    }

    function renderGarages(garages) {
        const list = $('garage-list');
        list.innerHTML = '';
        garages.forEach((garage) => {
            const label = document.createElement('label');
            label.className = 'flex cursor-pointer items-start gap-3 rounded-lg border border-slate-300 p-3 has-[:checked]:border-teal-600 has-[:checked]:bg-teal-50';
            label.innerHTML = `
                <input type="radio" name="garage_id" value="${garage.id}" class="mt-1">
                <span><span class="font-semibold">${garage.code}</span><br>
                <span class="text-sm text-slate-600">Harian ${rupiah(garage.daily_price)}${garage.kwh_price ? ` · Listrik ${rupiah(garage.kwh_price)}/kWh` : ''}</span><br>
                <span class="text-sm font-semibold">Total ${rupiah(garage.total_cost)}</span></span>`;
            label.querySelector('input').addEventListener('change', () => showCost(garage));
            list.appendChild(label);
        });
    }

    async function checkAvailability() {
        resetQuote('Memeriksa garasi tersedia...');
        const data = new FormData(form);
        data.delete('payment_proof');
        data.delete('garage_id');

        const response = await fetch(form.dataset.quoteUrl, {
            method: 'POST',
            headers: { Accept: 'application/json' },
            body: data,
        });
        const json = await response.json();

        if (!response.ok) {
            const errors = json.errors ? Object.values(json.errors).flat() : [json.message];
            resetQuote(errors.join(' '));
            return;
        }

        $('end_date').value = toDate(json.end_date).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
        $('quote-message').textContent = json.garages.length
            ? `${json.garages.length} garasi tersedia untuk ${json.duration_days} hari (${json.start_date} s/d ${json.end_date}). Pilih satu:`
            : 'Tidak ada garasi tipe ini yang tersedia pada periode tersebut.';
        renderGarages(json.garages);
    }

    fields.forEach((name) => {
        const el = $(name);
        if (!el) return;
        el.addEventListener('change', () => { refreshVehicleFields(); refreshPeriod(); resetQuote(); });
        el.addEventListener('input', () => { refreshPeriod(); });
    });
    $('check-btn').addEventListener('click', checkAvailability);

    refreshVehicleFields();
    refreshPeriod();
}
