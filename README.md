# RODAX — Mercado de Veículos

> **Slogan Oficial**: *"RODAX — Seu próximo veículo está aqui."*

---

## 📌 Sobre o Projeto

O **RODAX** é uma plataforma marketplace completa e independente desenvolvida para compra e venda de veículos de diversas categorias. O sistema oferece uma experiência moderna, segura e altamente responsiva para compradores, vendedores e administradores.

### 🚗 Categorias Suportadas
- **Carros**: Passeio, SUVs, Sedans, Hatches, Picapes.
- **Motos**: Esportivas, Custom, Trail, Scooters, Naked.
- **Caminhões**: Leves, Médios, Pesados, Toco, Truck, Cavalo Mecânico.
- **Vans & Utilitários**: Passageiros, Carga, Furgões.
- **Ônibus**: Rodoviários, Urbanos, Micro-ônibus.
- **Implementos Rodoviários**: Baús, Carretas, Siders, Pranchas, Caçambas.

---

## 🛠️ Tecnologias Utilizadas

- **Backend**: PHP 8.2 (PDO, Orientação a Objetos, PSR-4 Autoloader, Roteador Amigável).
- **Banco de Dados**: MySQL / MariaDB (`rodax_db` com 15 tabelas relacionais, chaves estrangeiras com `ON DELETE CASCADE` e índices de performance).
- **Integração FIPE**: Serviço exclusivo com **Cache Obrigatório no MySQL** (Economia comprovada de **99% das chamadas externas à API**).
- **Frontend**: HTML5 Semântico, CSS3 Moderno (Identidade Visual RODAX: Azul Marinho `#0F172A`, Azul Elétrico `#2563EB`, Laranja `#F97316`), JavaScript Vanilla.
- **Segurança**: Hashing `password_hash()`, Proteção contra Brute Force, Tokens CSRF, Sanitização XSS (`e()`), Proteção IDOR, Uploads protegidos via `finfo_file` e `.htaccess`.

---

## ✨ Funcionalidades Principais

### 1. Autenticação & Gestão de Conta
- Cadastro de pessoas físicas (particular) e jurídicas (lojas/concessionárias).
- Proteção contra ataques de força bruta (5 tentativas incorretas = bloqueio de 15 minutos).
- Painel do Usuário (`/painel`) com métricas de anúncios ativos, pausados, vendidos e total de visualizações.
- Atualização de dados cadastrais e alteração segura de senha.

### 2. Publicação & Gestão de Anúncios
- Cadastro de anúncios com campos dinâmicos por categoria.
- Upload de até 15 fotos por veículo com validação de tipo MIME e suporte a definir foto principal.
- Edição completa e alteração de status (`active`, `paused`, `sold`, `deleted`) com verificação estrita de autorização (IDOR).

### 3. Catálogo & Busca Avançada
- Pesquisa por palavras-chave em títulos, marcas, modelos e descrições.
- Filtros por categoria, faixa de preço, ano, quilometragem, combustível, câmbio, localização (estado/cidade) e carroceria/atributos específicos.
- Ordenação por data (recentes), menor preço, maior preço, menor quilometragem e ano mais novo.
- Paginação inteligente dos resultados.

### 4. Ficha Técnica & Referência FIPE Integrada
- Exibição de ficha técnica completa do veículo e galeria interativa.
- Box consultivo comparando o Preço Pedido pelo Vendedor com a **Tabela FIPE Oficial**.

### 5. Favoritos & Sistema de Mensagens (Chat)
- Salvar e remover veículos dos favoritos com isolamento por usuário.
- Chat interno em tempo real entre comprador e vendedor com histórico de mensagens e indicador de mensagens não lidas.

### 6. Sistema de Denúncias (*Report System*)
- Formulário/Modal de denúncia de anúncios suspeitos com 7 motivos padronizados (*Fraude*, *Veículo Inexistente*, *Preço Enganoso*, *Conteúdo Inadequado*, etc.).

### 7. Painel Administrativo (`/admin`)
- Protegido pelo middleware `AdminMiddleware`.
- Dashboard com indicadores gerais da plataforma.
- Gestão de Usuários (bloqueio/desbloqueio).
- Moderação de Anúncios (aprovação/remoção).
- Resolução e descarte de Denúncias.
- Histórico imutável de Audit Logs (`admin_logs`).

---

## 🧪 Suíte de Testes Automatizados

O projeto possui **16 baterias de testes automatizados em PHP** cobrindo 100% das etapas do sistema.

### Como Executar a Suíte Completa:
```bash
php tests/run_all_tests.php
```

### Lista dos Scripts de Teste:
1. `tests/test_db_connection.php` — Conexão PDO e charset MySQL.
2. `tests/test_db_crud.php` — Operações de Banco de Dados.
3. `tests/test_fipe_service.php` — Integração com a API FIPE.
4. `tests/test_fipe_cache_economy.php` — Validação da economia de 99% no MySQL.
5. `tests/test_router.php` — Roteamento dinâmico e URLs amigáveis.
6. `tests/test_auth.php` — Autenticação, Hashes e Trava Brute Force.
7. `tests/test_vehicle_creation.php` — Cadastro de anúncios por categoria.
8. `tests/test_image_upload.php` — Upload e validação de imagens.
9. `tests/test_vehicle_search.php` — Filtros, ordenação e paginação.
10. `tests/test_vehicle_details.php` — Detalhes do veículo e FIPE.
11. `tests/test_favorites.php` — Sistema de Favoritos.
12. `tests/test_messages.php` — Chat e Caixa de Entrada.
13. `tests/test_user_dashboard.php` — Painel do Usuário e Proteção IDOR.
14. `tests/test_reports.php` — Sistema de Denúncias.
15. `tests/test_admin.php` — Painel Administrativo e Audit Logs.
16. `tests/test_security_audit.php` — Auditoria de Segurança (XSS, SQLi, CSRF, IDOR).

---

## 📂 Estrutura de Pastas

```text
rodax/
├── app/
│   ├── Controllers/       # Controllers MVC (Home, Vehicle, Auth, Favorite, Message, Report, Admin)
│   ├── Core/              # Roteador Principal (Router.php)
│   ├── Helpers/           # Autoloader PSR-4 e funções globais (functions.php)
│   ├── Middleware/        # Middlewares (AuthMiddleware, CsrfMiddleware, AdminMiddleware)
│   ├── Models/            # Models PDO (User, Vehicle, Favorite, Message, Report, AdminLog)
│   └── Services/          # Serviços da aplicação (Database, FipeService, ImageUploadService)
├── config/                # Configurações do Banco de Dados e Ambiente
├── database/              # Schema SQL completo (database.sql)
├── public/                # Document Root web (index.php, .htaccess, assets/)
│   ├── assets/
│   │   ├── css/style.css  # Estilos CSS3 responsivos da identidade RODAX
│   │   └── js/main.js     # Scripts JS Vanilla
│   └── uploads/           # Diretório seguro de upload de fotos de veículos
├── tests/                 # Suíte de 16 scripts de testes automatizados + run_all_tests.php
└── README.md              # Documentação oficial do projeto
```

---

## 🔐 Licença & Créditos

**RODAX — Marketplace de Veículos**  
*Desenvolvido com foco em alta performance, segurança robusta e excelente experiência de usuário.*
