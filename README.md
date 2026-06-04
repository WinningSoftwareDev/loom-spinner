# Loom Spinner CLI

<p>
<!-- Version Badge -->
<img src="https://img.shields.io/badge/Version-3.5.0-blue" alt="Version 3.5.0">
<!-- License Badge -->
<img src="https://img.shields.io/badge/License-GPL--3.0--or--later-40adbc" alt="License GPL-3.0-or-later">
</p>

Loom Spinner is a streamlined environment management tool for PHP developers.

It makes launching minimal, pre-configured Docker containers effortless, providing a fast, consistent, and hassle-free 
workflow for your projects.

Run simple commands from anywhere on your system to manage your environments.

> This project is built for Linux and probably won't work on MacOS. I'm 99% certain it won't work on Windows. MacOS/Windows
> support _may_ be considered in future.

# At a Glance

Effortlessly create custom Docker environments for each of your PHP projects. A default Loom Spinner environment provides:

- **PHP 8.4** (with XDebug & OpCache)
- **Nginx**
- **MySQL 9.3**
- **NodeJS 25** (Node, NPM, & NPX)

Your project directory is automatically mounted into the PHP container, and the `public` directory is served via Nginx at:

```shell
https://{project-name}.app
``` 

You can access the container directly from your terminal to run tests or other commands in an isolated environment.

# Installation

**Requirements:**
- Composer
- Docker Desktop or Docker Engine

In addition, this package uses `mkcert` to create self-signed local SSL certificates. Install it on your system:

```bash
sudo apt update && sudo apt install mkcert libnss3
mkcert -install
```

To install Loom Spinner globally, run:

```shell
composer global require winningsoftware/loom-spinner
```

# Quick Start

Spin up your project and add the hosts entry in a single sequence:

```shell
cd /path/to/my-project
loom spin:up my-project .
sudo loom env:hosts:add my-project
```

> ✅ This will create the Docker containers (PHP, Nginx, MySQL) and ensure your system can resolve https://my-project.app 
> for clean URLs.

# Usage

Launch a new environment:

```shell
loom spin:up my-project /path/to/my-project
```

Or from the project directory:

```shell
cd /path/to/my-project
loom spin:up my-project .
```

## Hosts Entry

To access your project via the browser, add an entry to `/etc/hosts`:

```shell
sudo loom env:hosts:add my-project
```

## Database Credentials

Your default database credentials are:

| Username | Password |
|----------|----------|
| root     | docker   |

To see which port your database container is using, run:

```shell
loom env:list
```

## Managing Your Environment

| Action                  | Command                       |
|-------------------------|-------------------------------|
| Stop containers         | `loom spin:stop my-project`   |
| Start containers        | `loom spin:start my-project`  |
| Attach to PHP container | `loom spin:attach my-project` |
| Destroy environment     | `loom spin:down my-project`   |
| List all environments   | `loom env:list`               |

---

Happy spinning! 🧵
