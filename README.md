# 9MintDocker

Automated a production-ready containerization architecture for a collaborative web application, integrating Tailscale Funnel to enable secure, instant public hosting directly from local environments.

## 🔒 Local SSL Setup
(Optional)
This project uses HTTPS for localhost. Before running Docker:
1. Generate your local certificates (e.g., using `mkcert`).
2. Save them in the `/certs` folder as `localhost.crt` and `localhost.key`.

---

## 📋 Environment Configuration

Before spinning up the containers, you must set up your local environment variables:

1. Copy the template file to create your active configuration:
```bash
   cp .env.example .env
