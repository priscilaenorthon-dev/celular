const API_BASE = `${window.location.origin}/celular/api`;

const ui = {
    sections: {
        auth: document.getElementById('auth'),
        client: document.getElementById('clientArea'),
        admin: document.getElementById('adminArea'),
    },
    forms: {
        login: document.getElementById('loginForm'),
        ticket: document.getElementById('ticketForm'),
        hose: document.getElementById('hoseForm'),
        part: document.getElementById('partForm'),
        rental: document.getElementById('rentalForm'),
        maintenance: document.getElementById('maintenanceForm'),
        company: document.getElementById('companyForm'),
        user: document.getElementById('userForm'),
    },
    lists: {
        myItems: document.getElementById('myItems'),
        myTickets: document.getElementById('myTickets'),
        hoses: document.getElementById('hosesList'),
        parts: document.getElementById('partsList'),
        rentals: document.getElementById('rentalsList'),
        maints: document.getElementById('maintsList'),
        tickets: document.getElementById('ticketsList'),
        clients: document.getElementById('clientsList'),
    },
    badges: {
        total: document.getElementById('kpiTotal'),
        uso: document.getElementById('kpiUso'),
        manut: document.getElementById('kpiManut'),
        atrasado: document.getElementById('kpiAtraso'),
    },
    selects: {
        company: document.getElementById('companySelect'),
    },
    userName: document.getElementById('userName'),
    adminName: document.getElementById('adminName'),
    logoutBtns: document.querySelectorAll('.btn-logout'),
    tabButtons: document.querySelectorAll('.tab-btn'),
    tabPanes: document.querySelectorAll('.tab-pane'),
    refreshAdmin: document.getElementById('refreshAdmin'),
};

async function api(path, options = {}) {
    const opts = {
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        ...options,
    };
    if (opts.body instanceof FormData) {
        delete opts.headers['Content-Type'];
    }
    const res = await fetch(`${API_BASE}${path}`, opts);
    if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.error || 'Erro ao comunicar com API');
    }
    return res.json();
}

function show(section) {
    Object.values(ui.sections).forEach(el => el.style.display = 'none');
    ui.sections[section].style.display = 'block';
}

function renderList(target, items, formatter) {
    if (!target) return;
    target.innerHTML = items.length ? items.map(formatter).join('') : '<div class="item">Nenhum registro</div>';
}

function statusPill(state) {
    return `<span class="status" data-state="${state}">${state.replace('_', ' ')}</span>`;
}

function setTab(name) {
    ui.tabButtons.forEach(btn => btn.classList.toggle('active', btn.dataset.tabTarget === name));
    ui.tabPanes.forEach(pane => pane.classList.toggle('active', pane.dataset.tab === name));
}

function fillCompanies(companies = []) {
    if (!ui.selects.company) return;
    const opts = [`<option value="">Selecione a empresa</option>`].concat(
        companies.map(c => `<option value="${c.id}">${c.nome}</option>`)
    );
    ui.selects.company.innerHTML = opts.join('');
}

async function loadClientTickets() {
    try {
        const ticketsRes = await api('/tickets');
        renderList(ui.lists.myTickets, ticketsRes.tickets, t => `
            <div class="item">
                <strong>${t.descricao}</strong>
                <div>Urgencia: <span class="badge">${t.urgencia}</span></div>
                <small>Status: ${t.status}</small>
            </div>
        `);
    } catch (err) {
        alert('Erro ao carregar chamados: ' + err.message);
    }
}

async function loadClient() {
    show('client');
    const [rentalsRes, ticketsRes] = await Promise.all([
        api('/rentals'),
        api('/tickets'),
    ]);
    const items = rentalsRes.rentals.map(r => ({
        nome: `${r.item_type === 'hose' ? 'Mangueira' : 'Pe\u00e7a'} ${r.serial || ''}`,
        tipo: r.item_tipo || '',
        status: r.status,
        saida: r.data_saida,
        retorno: r.previsao_retorno,
    }));
    renderList(ui.lists.myItems, items, i => `
        <div class="item">
            <strong>${i.nome}</strong>
            <div>${i.tipo}</div>
            <div>${statusPill(i.status)}</div>
            <small>Saida: ${i.saida} - Prev. retorno: ${i.retorno}</small>
        </div>
    `);
    renderList(ui.lists.myTickets, ticketsRes.tickets, t => `
        <div class="item">
            <strong>${t.descricao}</strong>
            <div>Urgencia: <span class="badge">${t.urgencia}</span></div>
            <small>Status: ${t.status}</small>
        </div>
    `);
}

async function loadAdmin() {
    show('admin');
    setTab('hose');
    const [hosesRes, partsRes, rentalsRes, maintRes, ticketsRes, clientsRes, companiesRes] = await Promise.all([
        api('/hoses'),
        api('/parts'),
        api('/rentals'),
        api('/maintenance'),
        api('/tickets'),
        api('/users?tipo=cliente'),
        api('/companies'),
    ]);

    renderList(ui.lists.hoses, hosesRes.hoses, h => `
        <div class="item">
            <strong>${h.serial} - ${h.tipo}</strong>
            <div>Bitola ${h.bitola} - ${h.pressao} - ${h.comprimento}</div>
            <div>${statusPill(h.status)}</div>
            <small>${h.marca || ''} - Compra: ${h.data_compra || '---'}</small>
        </div>
    `);
    renderList(ui.lists.parts, partsRes.parts, p => `
        <div class="item">
            <strong>${p.serial} - ${p.tipo}</strong>
            <div>Aplicacao: ${p.aplicacao || '--'}</div>
            <div>${statusPill(p.status)}</div>
            <small>${p.marca || ''} - Compra: ${p.data_compra || '---'}</small>
        </div>
    `);
    renderList(ui.lists.rentals, rentalsRes.rentals, r => `
        <div class="item">
            <strong>${r.item_tipo} ${r.serial}</strong>
            <div>Cliente: ${r.cliente || r.user_id}</div>
            <div>${statusPill(r.status)}</div>
            <small>Saida: ${r.data_saida} - Prev. retorno: ${r.previsao_retorno}</small>
        </div>
    `);
    renderList(ui.lists.maints, maintRes.maintenance, m => `
        <div class="item">
            <strong>${m.item_type} #${m.item_id}</strong>
            <div>${m.motivo}</div>
            <div>${statusPill(m.status)}</div>
            <small>Prevista: ${m.data_prevista}</small>
        </div>
    `);
    renderList(ui.lists.tickets, ticketsRes.tickets, t => `
        <div class="item">
            <strong>${t.descricao}</strong>
            <div>Cliente #${t.user_id} - ${t.item_type} ${t.item_id}</div>
            <div>${statusPill(t.status)}</div>
            <small>Urgencia: ${t.urgencia}</small>
        </div>
    `);
    renderList(ui.lists.clients, clientsRes.users, c => `
        <div class="item">
            <strong>${c.nome}</strong>
            <div>${c.email}</div>
            <small>${c.telefone || ''} ${c.company ? ' - ' + c.company : ''}</small>
        </div>
    `);
    fillCompanies(companiesRes.companies);

    const totalItens = hosesRes.hoses.length + partsRes.parts.length;
    const manut = maintRes.maintenance.filter(m => m.status !== 'concluida').length;
    const atrasado = rentalsRes.rentals.filter(r => r.status === 'atrasado').length;
    const emUso = rentalsRes.rentals.filter(r => ['alugado', 'em uso'].includes(r.status)).length;
    ui.badges.total.textContent = totalItens;
    ui.badges.manut.textContent = manut;
    ui.badges.atrasado.textContent = atrasado;
    ui.badges.uso.textContent = emUso;
}

function handleAuth(user) {
    if (user.tipo === 'admin') {
        ui.adminName.textContent = user.nome;
        loadAdmin();
    } else {
        ui.userName.textContent = user.nome;
        loadClient();
    }
}

async function bootstrap() {
    try {
        const { user } = await api('/me');
        handleAuth(user);
    } catch (e) {
        show('auth');
    }
}

ui.forms.login?.addEventListener('submit', async e => {
    e.preventDefault();
    const form = new FormData(e.target);
    try {
        const res = await api('/login', {
            method: 'POST',
            body: JSON.stringify({
                email: form.get('email'),
                password: form.get('password'),
            })
        });
        handleAuth(res.user);
    } catch (err) { alert(err.message); }
});

ui.forms.ticket?.addEventListener('submit', async e => {
    e.preventDefault();
    const form = new FormData(e.target);
    try {
        const itemId = form.get('item_id') || 0;
        await api('/tickets', {
            method: 'POST',
            body: JSON.stringify({
                item_type: form.get('item_type'),
                item_id: itemId,
                descricao: form.get('descricao'),
                urgencia: form.get('urgencia'),
            })
        });
        alert('Chamado criado.');
        await loadClientTickets();
        e.target.reset();
    } catch (err) { alert(err.message); }
});

ui.forms.hose?.addEventListener('submit', async e => {
    e.preventDefault();
    const form = new FormData(e.target);
    try {
        await api('/hoses', { method: 'POST', body: form });
        alert('Mangueira cadastrada.');
        loadAdmin();
        e.target.reset();
    } catch (err) { alert(err.message); }
});

ui.forms.part?.addEventListener('submit', async e => {
    e.preventDefault();
    const form = new FormData(e.target);
    try {
        await api('/parts', { method: 'POST', body: form });
        alert('Pe\u00e7a cadastrada.');
        loadAdmin();
        e.target.reset();
    } catch (err) { alert(err.message); }
});

ui.forms.rental?.addEventListener('submit', async e => {
    e.preventDefault();
    const form = new FormData(e.target);
    try {
        await api('/rentals', {
            method: 'POST',
            body: JSON.stringify({
                user_id: form.get('user_id'),
                item_type: form.get('item_type'),
                item_id: form.get('item_id'),
                data_saida: form.get('data_saida'),
                previsao_retorno: form.get('previsao_retorno'),
                condicao_entrega: form.get('condicao_entrega'),
            })
        });
        alert('Aluguel registrado.');
        loadAdmin();
        e.target.reset();
    } catch (err) { alert(err.message); }
});

ui.forms.maintenance?.addEventListener('submit', async e => {
    e.preventDefault();
    const form = new FormData(e.target);
    try {
        await api('/maintenance', {
            method: 'POST',
            body: JSON.stringify({
                item_type: form.get('item_type'),
                item_id: form.get('item_id'),
                data_prevista: form.get('data_prevista'),
                motivo: form.get('motivo'),
                checklist: form.get('checklist'),
                custo: form.get('custo'),
                status: form.get('status'),
            })
        });
        alert('Manutencao criada.');
        loadAdmin();
        e.target.reset();
    } catch (err) { alert(err.message); }
});

ui.tabButtons.forEach(btn => {
    btn.addEventListener('click', () => setTab(btn.dataset.tabTarget));
});

ui.forms.company?.addEventListener('submit', async e => {
    e.preventDefault();
    const form = new FormData(e.target);
    try {
        const res = await api('/companies', {
            method: 'POST',
            body: JSON.stringify({
                nome: form.get('nome'),
                cnpj: form.get('cnpj'),
            })
        });
        const companies = await api('/companies');
        fillCompanies(companies.companies || []);
        if (ui.selects.company) {
            const newId = res.company_id ? String(res.company_id) : `local-${Date.now()}`;
            // se não veio da API, garante a opção manualmente
            if (!res.company_id) {
                const opt = document.createElement('option');
                opt.value = newId;
                opt.textContent = form.get('nome');
                ui.selects.company.appendChild(opt);
            }
            ui.selects.company.value = newId;
        }
        alert('Empresa criada.');
        await loadAdmin();
        e.target.reset();
    } catch (err) { alert(err.message); }
});

ui.forms.user?.addEventListener('submit', async e => {
    e.preventDefault();
    const form = new FormData(e.target);
    try {
        await api('/users', {
            method: 'POST',
            body: JSON.stringify({
                nome: form.get('nome'),
                email: form.get('email'),
                telefone: form.get('telefone'),
                tipo: form.get('tipo'),
                company_id: form.get('company_id'),
                password: form.get('password'),
            })
        });
        alert('Usuário criado.');
        loadAdmin();
        e.target.reset();
    } catch (err) { alert(err.message); }
});

ui.refreshAdmin?.addEventListener('click', () => loadAdmin());

ui.logoutBtns.forEach(btn => btn.addEventListener('click', async () => {
    await api('/logout', { method: 'POST' });
    show('auth');
}));

bootstrap();
