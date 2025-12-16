# 🧩 Plataforma TCC — Ambiente Docker

Este projeto utiliza **Laravel**, **Nginx**, **PHP-FPM**, **MariaDB**, **Redis**, **Supervisor** e **phpMyAdmin**, totalmente configurados via **Docker Compose**.

---

## 🚀 Requisitos

Antes de começar, verifique se você tem instalado:

- [Docker](https://www.docker.com/get-started)
- [Docker Compose](https://docs.docker.com/compose/)
- (Opcional) [Git](https://git-scm.com/)

---

## 🏗️ Estrutura do Projeto

.DOCKER_FILES/
├── php-fpm/ # Configurações do PHP (xdebug, etc.)
├── nginx/ # Configurações do Nginx
├── mariadb/ # Dados persistidos do banco
├── supervisor/ # Configuração do Supervisor
└── logs/ # Logs do Nginx
app/ # Código Laravel
docker-compose.yml # Orquestra os containers

---

## ⚙️ Variáveis de Ambiente

Crie o arquivo `.env` na raiz do projeto (caso não exista) com base no `.env.example` e ajuste as configurações do banco:

```env
DB_CONNECTION=mysql
DB_HOST=mariadb
DB_PORT=3306
DB_DATABASE=tcc_database
DB_USERNAME=root
DB_PASSWORD=root

REDIS_HOST=redis
REDIS_PORT=6379

⚠️ Importante: o DB_HOST deve ser mariadb — não use localhost, pois os serviços se comunicam via rede Docker.

🧱 Construindo e Subindo o Ambiente

Execute o comando abaixo na raiz do projeto:

docker compose up -d --build

Esse comando:

- Faz o build da imagem PHP-FPM personalizada (com Composer, Xdebug e dependências)

- Inicializa automaticamente:

-- 🐘 PHP-FPM (Laravel)

-- 🌐 Nginx (porta 8080)

-- 🗄️ MariaDB (porta 3305)

-- 🧰 phpMyAdmin (porta 82)

-- ⚡ Redis

-- 🧩 Supervisor (para workers)

🌍 Acessos Rápidos
Serviço	                URL / Acesso
Aplicação Laravel	    http://localhost:8080
phpMyAdmin	            http://localhost:82
Banco de Dados	        mariadb:3306 (interno) / localhost:3305 (externo)
Redis	                redis:6379
Usuário DB	            root
Senha DB	            root

🧪 Comandos Úteis
🔄 Ver logs

docker compose logs -f

