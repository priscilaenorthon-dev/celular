<?php
// Front controller for the responsive PWA-like dashboard
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestao de Mangueiras | Celular Rentals</title>
    <link rel="manifest" href="/celular/public/manifest.json">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Sora:wght@400;600;700&display=swap">
    <link rel="stylesheet" href="/celular/assets/styles.css">
</head>
<body>
    <div class="shell">
        <div class="topbar">
            <div class="brand">
                <div class="brand-badge">HX</div>
                <div>
                    <div>HydroX</div>
                    <small style="color: var(--muted);">Mangueiras & Pe&ccedil;as premium</small>
                </div>
            </div>
        </div>

        <section id="auth">
            <div class="hero card" style="padding: 32px; margin-bottom: 26px; position: relative; overflow:hidden;">
                <div style="position:absolute; inset:0; background: radial-gradient(circle at 20% 30%, rgba(33,195,255,0.22), transparent 45%), radial-gradient(circle at 80% 10%, rgba(245,192,109,0.25), transparent 40%); filter: blur(12px); opacity:0.8; pointer-events:none;"></div>
                <div class="section-title">
                    <div>
                        <h1 style="margin-top:0;">Login rapido e seguro</h1>
                        <p style="margin:0;color:var(--muted);">Acesse com as credenciais fornecidas e teste o fluxo completo.</p>
                        <p style="margin:6px 0 0;color:var(--text);font-weight:600;">Mangueiras & Pe&ccedil;as premium com controle total.</p>
                    </div>
                </div>
                <div class="grid two" style="margin-top:18px; align-items:center;">
                    <div>
                        <form id="loginForm" class="stack" style="padding: 6px 0;">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" required placeholder="admin@empresa.com ou user@empresa.com" autocomplete="email">
                            </div>
                            <div class="form-group">
                                <label>Senha</label>
                                <input type="password" name="password" required placeholder="Digite a senha uma unica vez" autocomplete="current-password" style="box-shadow: inset 0 0 0 1px rgba(255,255,255,0.08);">
                            </div>
                            <button class="btn" type="submit" style="width:100%;">Entrar</button>
                        </form>
                    </div>
                    <div class="card" style="background: linear-gradient(145deg, rgba(33,195,255,0.18), rgba(245,192,109,0.14)); border-color: rgba(33,195,255,0.25); box-shadow: 0 20px 50px rgba(0,0,0,0.25);">
                        <h3 style="margin-top:0;">Credenciais de teste</h3>
                        <div class="list">
                            <div class="item">
                                <strong>Administrador</strong>
                                <div>admin@empresa.com</div>
                                <small>Senha: <code>admin123</code></small>
                            </div>
                            <div class="item">
                                <strong>Cliente</strong>
                                <div>user@empresa.com</div>
                                <small>Senha: <code>user123</code></small>
                            </div>
                        </div>
                        <p style="color:var(--muted); margin-top:10px;">Use um unico campo de senha: basta inserir a credencial e entrar.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="clientArea" style="display:none;">
            <div class="section-title">
                <h3>Area do cliente</h3>
                <div class="pill">
                    <span id="userName">Cliente</span>
                    <button class="btn ghost btn-logout" type="button">Sair</button>
                </div>
            </div>
            <div class="grid two">
                <div class="card">
                    <div class="section-title">
                        <h3>Meus itens</h3>
                        <span class="badge">Alugados</span>
                    </div>
                    <div id="myItems" class="list"></div>
                </div>
                <div class="card">
                    <div class="section-title">
                        <h3>Solicitar manutencao</h3>
                        <span class="badge">Chamados</span>
                    </div>
                    <form id="ticketForm" class="stack">
                        <div class="form-inline">
                            <div class="form-group">
                                <label>Tipo</label>
                                <select name="item_type">
                                    <option value="hose">Mangueira</option>
                                    <option value="part">Pe&ccedil;a</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>ID do item</label>
                                <input name="item_id" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Urgencia</label>
                            <select name="urgencia">
                                <option value="baixa">Baixa</option>
                                <option value="media" selected>Media</option>
                                <option value="alta">Alta</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Descricao</label>
                            <textarea name="descricao" required rows="3" placeholder="Descreva o problema"></textarea>
                        </div>
                        <button class="btn" type="submit">Abrir chamado</button>
                    </form>
                </div>
            </div>
            <div class="card" style="margin-top:18px;">
                <div class="section-title">
                    <h3>Historico de chamados</h3>
                </div>
                <div id="myTickets" class="list"></div>
            </div>
        </section>

        <section id="adminArea" style="display:none;">
            <div class="section-title">
                <h3>Painel do administrador</h3>
                <div class="pill">
                    <span id="adminName">Admin</span>
                    <button class="btn ghost" type="button" id="refreshAdmin">Atualizar</button>
                    <button class="btn ghost btn-logout" type="button">Sair</button>
                </div>
            </div>
            <div class="kpis">
                <div class="kpi"><h4>Total de itens</h4><span id="kpiTotal">0</span><small>Mangueiras + Pe&ccedil;as</small></div>
                <div class="kpi"><h4>Em uso</h4><span id="kpiUso">0</span><small>Alugados/ativos</small></div>
                <div class="kpi"><h4>Manutencao</h4><span id="kpiManut">0</span><small>Ordem aberta</small></div>
                <div class="kpi"><h4>Atrasadas</h4><span id="kpiAtraso">0</span><small>Entrega vencida</small></div>
            </div>

            <div class="card" style="margin-top:18px;">
                <div class="section-title">
                    <h3>Operacoes rapidas</h3>
                </div>
                <div class="tab-nav">
                    <button class="tab-btn active" data-tab-target="hose">Nova Mangueira</button>
                    <button class="tab-btn" data-tab-target="part">Nova Pe&ccedil;a</button>
                    <button class="tab-btn" data-tab-target="rental">Registrar Aluguel</button>
                    <button class="tab-btn" data-tab-target="maint">Manutencao</button>
                </div>
                <div class="tab-pane active" data-tab="hose">
                    <form id="hoseForm" class="stack" enctype="multipart/form-data">
                        <div class="form-inline">
                            <div class="form-group"><label>Serial</label><input name="serial" required></div>
                            <div class="form-group"><label>Tipo</label><input name="tipo" required></div>
                        </div>
                        <div class="form-inline">
                            <div class="form-group"><label>Bitola</label><input name="bitola" required></div>
                            <div class="form-group"><label>Pressao</label><input name="pressao" required></div>
                            <div class="form-group"><label>Comprimento</label><input name="comprimento" required></div>
                        </div>
                        <div class="form-inline">
                            <div class="form-group"><label>Marca</label><input name="marca"></div>
                            <div class="form-group"><label>Data compra</label><input type="date" name="data_compra"></div>
                            <div class="form-group"><label>Status</label>
                                <select name="status">
                                    <option value="disponivel">Disponivel</option>
                                    <option value="alugado">Alugada</option>
                                    <option value="manutencao">Manutencao</option>
                                    <option value="condenado">Condenada</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group"><label>Observacoes</label><textarea name="observacoes"></textarea></div>
                        <div class="form-group"><label>Foto</label><input type="file" name="foto" accept="image/*"></div>
                        <button class="btn" type="submit">Cadastrar mangueira</button>
                    </form>
                </div>
                <div class="tab-pane" data-tab="part">
                    <form id="partForm" class="stack" enctype="multipart/form-data">
                        <div class="form-inline">
                            <div class="form-group"><label>Serial</label><input name="serial" required></div>
                            <div class="form-group"><label>Tipo</label><input name="tipo" required></div>
                        </div>
                        <div class="form-group"><label>Aplicacao</label><input name="aplicacao"></div>
                        <div class="form-inline">
                            <div class="form-group"><label>Marca</label><input name="marca"></div>
                            <div class="form-group"><label>Data compra</label><input type="date" name="data_compra"></div>
                            <div class="form-group"><label>Status</label>
                                <select name="status">
                                    <option value="disponivel">Disponivel</option>
                                    <option value="alugado">Alugada</option>
                                    <option value="manutencao">Manutencao</option>
                                    <option value="condenado">Condenada</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group"><label>Observacoes</label><textarea name="observacoes"></textarea></div>
                        <div class="form-group"><label>Foto</label><input type="file" name="foto" accept="image/*"></div>
                        <button class="btn alt" type="submit">Cadastrar Pe&ccedil;a</button>
                    </form>
                </div>
                <div class="tab-pane" data-tab="rental">
                    <form id="rentalForm" class="stack">
                        <div class="form-inline">
                            <div class="form-group"><label>Cliente (ID)</label><input name="user_id" required></div>
                            <div class="form-group"><label>Tipo de item</label>
                                <select name="item_type">
                                    <option value="hose">Mangueira</option>
                                    <option value="part">Pe&ccedil;a</option>
                                </select>
                            </div>
                            <div class="form-group"><label>ID do item</label><input name="item_id" required></div>
                        </div>
                        <div class="form-inline">
                            <div class="form-group"><label>Data saida</label><input type="date" name="data_saida" required></div>
                            <div class="form-group"><label>Previsao retorno</label><input type="date" name="previsao_retorno" required></div>
                            <div class="form-group"><label>Condicao entrega</label><input name="condicao_entrega" required></div>
                        </div>
                        <button class="btn" type="submit">Registrar aluguel</button>
                    </form>
                </div>
                <div class="tab-pane" data-tab="maint">
                    <form id="maintenanceForm" class="stack">
                        <div class="form-inline">
                            <div class="form-group"><label>Tipo</label>
                                <select name="item_type">
                                    <option value="hose">Mangueira</option>
                                    <option value="part">Pe&ccedil;a</option>
                                </select>
                            </div>
                            <div class="form-group"><label>ID do item</label><input name="item_id" required></div>
                            <div class="form-group"><label>Data prevista</label><input type="date" name="data_prevista" required></div>
                        </div>
                        <div class="form-group"><label>Motivo</label><input name="motivo" required></div>
                        <div class="form-group"><label>Checklist</label><textarea name="checklist" rows="2"></textarea></div>
                        <div class="form-inline">
                            <div class="form-group"><label>Custo</label><input type="number" step="0.01" name="custo"></div>
                            <div class="form-group"><label>Status</label>
                                <select name="status">
                                    <option value="aberta">Aberta</option>
                                    <option value="em andamento">Em andamento</option>
                                    <option value="concluida">Concluida</option>
                                </select>
                            </div>
                        </div>
                        <button class="btn alt" type="submit">Criar ordem</button>
                    </form>
                </div>
            </div>

            <div class="grid two" style="margin-top:18px;">
                <div class="card">
                    <div class="section-title"><h3>Clientes</h3></div>
                    <div id="clientsList" class="list"></div>
                </div>
                <div class="card">
                    <div class="section-title"><h3>Alugueis</h3></div>
                    <div id="rentalsList" class="list"></div>
                </div>
            </div>

            <div class="grid three" style="margin-top:18px;">
                <div class="card">
                    <div class="section-title"><h3>Mangueiras</h3></div>
                    <div id="hosesList" class="list"></div>
                </div>
                <div class="card">
                    <div class="section-title"><h3>Pe&ccedil;as</h3></div>
                    <div id="partsList" class="list"></div>
                </div>
                <div class="card">
                    <div class="section-title"><h3>Manutencoes</h3></div>
                    <div id="maintsList" class="list"></div>
                </div>
            </div>

            <div class="card" style="margin-top:18px;">
                <div class="section-title"><h3>Chamados de clientes</h3></div>
                <div id="ticketsList" class="list"></div>
            </div>

            <div class="grid two" style="margin-top:18px;">
                <div class="card">
                    <div class="section-title"><h3>Nova Empresa</h3></div>
                    <form id="companyForm" class="stack">
                        <div class="form-group"><label>Nome</label><input name="nome" required></div>
                        <div class="form-group"><label>CNPJ</label><input name="cnpj"></div>
                        <button class="btn" type="submit">Criar empresa</button>
                    </form>
                </div>
                <div class="card">
                    <div class="section-title"><h3>Novo Usuário</h3></div>
                    <form id="userForm" class="stack">
                        <div class="form-group"><label>Nome</label><input name="nome" required></div>
                        <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
                        <div class="form-inline">
                            <div class="form-group"><label>Telefone</label><input name="telefone"></div>
                            <div class="form-group"><label>Tipo</label>
                                <select name="tipo">
                                    <option value="cliente">Cliente</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group"><label>Empresa</label>
                            <select name="company_id" id="companySelect"></select>
                        </div>
                        <div class="form-group"><label>Senha</label><input type="password" name="password" required></div>
                        <button class="btn alt" type="submit">Criar usuário</button>
                    </form>
                </div>
            </div>
        </section>
    </div>

    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/celular/public/sw.js').catch(() => {});
        }
    </script>
    <script src="/celular/assets/app.js"></script>
</body>
</html>
