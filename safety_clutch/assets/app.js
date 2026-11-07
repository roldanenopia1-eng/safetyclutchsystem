/* Safety Clutch System - front-end logic */
const $ = (s, r = document) => r.querySelector(s);
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const peso = n => '₱' + Number(n).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
const starStr = n => n == null ? '☆☆☆☆☆' : '★'.repeat(Math.round(n)) + '☆'.repeat(5 - Math.round(n));

async function api(action, params = {}, post = false) {
  const qs = new URLSearchParams(params);
  let url = 'api.php?action=' + action, opt = {};
  if (post) opt = {method: 'POST', body: qs}; else url += '&' + qs.toString();
  const r = await fetch(url, opt);
  const j = await r.json();
  if (j.error) throw new Error(j.error);
  return j;
}

/* ---------------- Chat between passenger and driver ---------------- */
const chat = {id: null, last: 0, timer: null,
  start(bookingId) {
    if (this.id === bookingId) return;
    this.stop(); this.id = bookingId; this.last = 0;
    $('#chat-log').innerHTML = ''; $('#chat-card').classList.remove('hidden');
    this.poll(); this.timer = setInterval(() => this.poll(), 3000);
  },
  stop() { clearInterval(this.timer); this.id = null; const c = $('#chat-card'); if (c) c.classList.add('hidden'); },
  async poll() {
    if (!this.id) return;
    try {
      const j = await api('messages', {booking: this.id, since: this.last});
      const log = $('#chat-log');
      j.messages.forEach(m => {
        this.last = Math.max(this.last, +m.id);
        log.insertAdjacentHTML('beforeend', `<div class="msg ${m.mine ? 'mine' : ''}">${esc(m.body)}</div>`);
      });
      if (j.messages.length) log.scrollTop = log.scrollHeight;
    } catch (e) {}
  }
};
function wireChatForm() {
  const f = $('#chat-form'); if (!f) return;
  f.addEventListener('submit', async e => {
    e.preventDefault();
    const i = $('#chat-input'), body = i.value.trim();
    if (!body || !chat.id) return;
    i.value = '';
    try { await api('send_message', {booking: chat.id, body}, true); chat.poll(); } catch (err) { alert(err.message); }
  });
}

/* ---------------- Passenger page ---------------- */
function initPassenger() {
  const form = $('#book-form');
  const val = n => form.elements[n].value;

  async function quote() {
    try {
      const j = await api('quote', {distance: val('distance') || 0, vehicle: form.elements.vehicle.value});
      $('#q-fare').textContent = peso(j.fare);
      $('#q-disc').textContent = '-' + peso(j.discount) + (j.discount_pct ? ` (${j.discount_pct}%)` : '');
      $('#q-total').textContent = peso(j.total);
    } catch (e) {}
  }
  form.addEventListener('input', quote);
  form.addEventListener('change', () => {
    $('#gcash-wrap').classList.toggle('hidden', form.elements.payment.value !== 'gcash');
  });

  form.addEventListener('submit', async e => {
    e.preventDefault();
    const msg = $('#book-msg'); msg.classList.add('hidden');
    $('#book-btn').disabled = true;
    try {
      await api('book', {
        pickup: val('pickup'), dropoff: val('dropoff'), distance: val('distance'),
        vehicle: form.elements.vehicle.value, payment: form.elements.payment.value, gcash_ref: val('gcash_ref')
      }, true);
      form.reset(); $('#gcash-wrap').classList.add('hidden'); quote(); refresh();
    } catch (err) { msg.textContent = err.message; msg.classList.remove('hidden'); }
    $('#book-btn').disabled = false;
  });

  // Rating modal
  let rateId = null, stars = 0;
  const pick = $('#star-pick');
  pick.addEventListener('click', e => {
    if (!e.target.dataset.v) return;
    stars = +e.target.dataset.v;
    [...pick.children].forEach(s => s.classList.toggle('on', +s.dataset.v <= stars));
  });
  $('#rate-submit').addEventListener('click', async () => {
    if (!stars) return alert('Please pick a star rating.');
    try {
      await api('rate', {id: rateId, stars, comment: $('#rate-comment').value}, true);
      $('#rate-modal').classList.add('hidden'); stars = 0; $('#rate-comment').value = '';
      [...pick.children].forEach(s => s.classList.remove('on')); refresh();
    } catch (err) { alert(err.message); }
  });

  async function refresh() {
    let j; try { j = await api('passenger_state'); } catch (e) { return; }
    $('#my-discount').textContent = j.discount_pct + '%';
    $('#trip-count').textContent = `(${j.completed} completed trip${j.completed == 1 ? '' : 's'})`;
    const b = j.booking, body = $('#ride-body');
    if (!b) {
      body.className = 'muted'; body.innerHTML = 'No active ride. Book one to get picked up!'; chat.stop();
    } else {
      body.className = 'ride-info';
      let html = `<p><span class="pill ${b.status}">${esc(b.status.toUpperCase())}</span></p>
        <p>📍 <b>${esc(b.pickup)}</b> → <b>${esc(b.dropoff)}</b></p>
        <p>Total: <b>${peso(b.total)}</b> · ${esc(b.payment_method.toUpperCase())}${b.discount_pct ? ` · ${b.discount_pct}% discount` : ''}</p>`;
      if (b.status === 'pending') html += '<p class="muted">🔎 Looking for a driver near you…</p>';
      if (b.driver_id) {
        const s = b.driver_stats;
        html += `<div class="job"><b>🚘 ${esc(b.dname)}</b> <span class="stars">${starStr(s.avg)}</span>
          <div class="muted small">${s.avg ?? 'No ratings yet'}${s.count ? ` (${s.count})` : ''} · ${esc(s.label)}</div>
          <p>${esc(b.dvehicle)} · Plate <b>${esc(b.dplate)}</b></p>
          <p>📞 <a class="callbtn" href="tel:${esc(b.dphone)}">${esc(b.dphone)}</a></p>
          ${b.payment_method === 'gcash' ? '<p class="muted small">GCash ref: ' + esc(b.gcash_ref) + '</p>' : ''}
          <p class="muted small">${b.status === 'accepted' ? 'Your driver is on the way!' : 'Trip in progress. Ride safe!'}</p></div>`;
        chat.start(b.id);
      } else chat.stop();
      if (b.status !== 'ongoing') html += `<button class="btn danger small" id="cancel-btn" data-id="${b.id}">Cancel ride</button>`;
      body.innerHTML = html;
      const cb = $('#cancel-btn');
      if (cb) cb.onclick = async () => { if (confirm('Cancel this ride?')) { try { await api('cancel', {id: cb.dataset.id}, true); refresh(); } catch (e) { alert(e.message); } } };
    }
    // history
    $('#history tbody').innerHTML = j.history.length ? j.history.map(h => `<tr><td>${h.id}</td>
      <td>${esc(h.pickup)} → ${esc(h.dropoff)}</td><td>${esc(h.dname || '—')}</td><td>${peso(h.total)}</td>
      <td>${esc(h.payment_method)}</td><td><span class="pill ${h.status}">${esc(h.status)}</span></td></tr>`).join('')
      : '<tr><td colspan="6" class="muted">No trips yet.</td></tr>';
    // ask for rating
    if (j.unrated && $('#rate-modal').classList.contains('hidden')) {
      rateId = j.unrated.id; $('#rate-driver').textContent = 'How safely did ' + j.unrated.dname + ' drive?';
      $('#rate-modal').classList.remove('hidden');
    }
  }
  quote(); refresh(); wireChatForm();
  setInterval(refresh, 4000);
}

/* ---------------- Driver page ---------------- */
function initDriver() {
  const onlineBtn = $('#online-btn');
  onlineBtn.addEventListener('click', async () => { try { await api('toggle_online', {}, true); refresh(); } catch (e) { alert(e.message); } });

  async function act(action, id) { try { await api(action, {id}, true); refresh(); } catch (e) { alert(e.message); refresh(); } }

  async function refresh() {
    let j; try { j = await api('driver_state'); } catch (e) { return; }
    onlineBtn.textContent = j.online ? '🟢 Online — tap to go offline' : 'Go online';
    onlineBtn.classList.toggle('green', !!j.online);
    $('#earn-today').textContent = peso(j.earn.today); $('#earn-total').textContent = peso(j.earn.t); $('#trips-done').textContent = j.earn.n;
    $('#my-rating').innerHTML = `<span class="stars">${starStr(j.stats.avg)}</span>${j.stats.avg ?? '—'} · ${esc(j.stats.label)}`;

    const pl = $('#pending-list');
    if (!j.online) { pl.className = 'muted'; pl.textContent = 'Go online to see passengers waiting for a ride.'; }
    else if (j.active) { pl.className = 'muted'; pl.textContent = 'Finish your current trip to accept new bookings.'; }
    else if (!j.pending.length) { pl.className = 'muted'; pl.textContent = 'Waiting for passengers…'; }
    else {
      pl.className = '';
      pl.innerHTML = j.pending.map(p => `<div class="job"><b>${esc(p.pname)}</b> · ${p.distance_km} km<br>
        📍 ${esc(p.pickup)} → ${esc(p.dropoff)}<br>${peso(p.total)} · ${esc(p.payment_method.toUpperCase())}<br>
        <button class="btn small" data-accept="${p.id}">Accept booking</button></div>`).join('');
      pl.querySelectorAll('[data-accept]').forEach(b => b.onclick = () => act('accept', b.dataset.accept));
    }

    const a = j.active, ab = $('#active-body');
    if (!a) { ab.className = 'muted'; ab.textContent = 'No active trip.'; chat.stop(); }
    else {
      ab.className = 'ride-info';
      ab.innerHTML = `<p><span class="pill ${a.status}">${esc(a.status.toUpperCase())}</span></p>
        <p>👤 <b>${esc(a.pname)}</b> · 📞 <a class="callbtn" href="tel:${esc(a.pphone)}">${esc(a.pphone)}</a></p>
        <p>📍 <b>${esc(a.pickup)}</b> → <b>${esc(a.dropoff)}</b></p>
        <p>Collect: <b>${peso(a.total)}</b> via ${esc(a.payment_method.toUpperCase())}${a.payment_method === 'gcash' ? ' (ref ' + esc(a.gcash_ref) + ')' : ''}</p>
        ${a.status === 'accepted' ? `<button class="btn" id="start-btn">Passenger picked up — start trip</button>` : `<button class="btn green" id="done-btn">Complete trip</button>`}`;
      const s = $('#start-btn'), d = $('#done-btn');
      if (s) s.onclick = () => act('start', a.id);
      if (d) d.onclick = () => act('complete', a.id);
      chat.start(a.id);
    }
    $('#reviews').innerHTML = j.reviews.length ? j.reviews.map(r =>
      `<p><span class="stars">${starStr(r.stars)}</span> <b>${esc(r.pname)}</b>${r.comment ? ' — ' + esc(r.comment) : ''}</p>`).join('') : 'No reviews yet.';
  }
  refresh(); wireChatForm();
  setInterval(refresh, 4000);
}

/* ---------------- Register page ---------------- */
function initAuth() {
  const role = $('#role');
  if (role) role.addEventListener('change', () => $('#driver-fields').classList.toggle('hidden', role.value !== 'driver'));
}

/* ---------------- SafeBot ---------------- */
function initBot() {
  const panel = $('#bot'), log = $('#bot-log');
  const add = (t, me) => { log.insertAdjacentHTML('beforeend', `<div class="msg ${me ? 'mine' : ''}">${esc(t)}</div>`); log.scrollTop = log.scrollHeight; };
  $('#bot-toggle').onclick = () => { panel.classList.toggle('hidden'); if (!log.children.length) add("Hi! I'm SafeBot 🤖 Ask me about booking, fares, discounts, payment or ratings."); };
  $('#bot-close').onclick = () => panel.classList.add('hidden');
  $('#bot-form').addEventListener('submit', async e => {
    e.preventDefault();
    const i = $('#bot-input'), m = i.value.trim(); if (!m) return;
    add(m, true); i.value = '';
    try { const j = await api('chatbot', {message: m}, true); add(j.reply); } catch (err) { add('Sorry, I could not answer that right now.'); }
  });
}

document.addEventListener('DOMContentLoaded', () => {
  const page = document.body.dataset.page;
  initBot();
  if (page === 'passenger') initPassenger();
  if (page === 'driver') initDriver();
  if (page === 'auth') initAuth();
});
