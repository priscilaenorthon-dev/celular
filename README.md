# HydroX – Locação de mangueiras e peças (PHP 8 + MySQL)

Aplicativo responsivo estilo PWA para clientes e administradores controlarem aluguéis, manutenção e chamados de mangueiras/peças premium.

## Estrutura de pastas
- `config/config.php` – credenciais do MySQL e caminhos.
- `database.sql` – script completo para criar banco, empresas e usuários padrão.
- `api/` – endpoints REST em PHP.
- `public/` – frontend responsivo (PWA) e service worker.
- `assets/` – CSS/JS do app.
- `uploads/` – onde fotos são salvas.

## Instalação (XAMPP)
1) Copie a pasta para `htdocs` (ex.: `c:/xampp/htdocs/celular`).
2) Inicie Apache + MySQL pelo XAMPP.
3) Crie o banco:
```sql
SOURCE c:/xampp/htdocs/celular/database.sql;
```
ou execute o arquivo pelo phpMyAdmin.
4) Ajuste o usuário/senha do MySQL em `config/config.php` se necessário.
5) Acesse `http://localhost/celular/public/index.php`.

## Login inicial
- Admin: `admin@empresa.com` / `admin123`
- Cliente demo: `user@empresa.com` / `user123`
- Clientes podem se cadastrar pelo próprio app.

## Endpoints principais
- `POST /celular/api/register` – cadastro cliente.
- `POST /celular/api/login` – login (admin ou cliente).
- `POST /celular/api/logout`
- `GET /celular/api/me`
- `GET|POST|PUT /celular/api/hoses` – CRUD mangueiras.
- `GET|POST|PUT /celular/api/parts` – CRUD peças.
- `GET|POST|PUT /celular/api/rentals` – aluguéis/vínculos.
- `GET|POST|PUT /celular/api/maintenance` – ordens de manutenção.
- `GET|POST|PUT /celular/api/tickets` – chamados dos clientes.
- `GET /celular/api/users?tipo=cliente` – lista clientes (admin).
- `GET|POST /celular/api/companies` – gestão de empresas (admin).
- `POST /celular/api/users` – criação de usuários pelo admin.

Autenticação por sessão; requisições precisam enviar cookies (já configurado no `assets/app.js` com `credentials: 'include'`).

## Notas de uso
- Uploads são guardados em `uploads/`; garanta permissão de escrita.
- `base_url` no `config/config.php` está como `/celular`. Ajuste se o diretório mudar.
- Service worker pré-carrega CSS/JS para uso offline básico.
- UI com tema gradiente claro e abas no painel admin para cadastros rápidos (mangueira, peça, aluguel, manutenção, empresa e usuário).

## Próximos passos sugeridos
- Criar telas separadas para edição detalhada de itens e dashboards específicos.
- Adicionar notificações push (Web Push) para alertas de manutenção e atrasos.
- Incluir validações adicionais e logs de auditoria por usuário.
