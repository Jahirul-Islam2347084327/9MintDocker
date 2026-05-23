# 9MintDocker
Automated a production-ready containerization architecture for a collaborative web application, integrating Tailscale Funnel to enable secure, instant public hosting directly from local environments.

## 🔒 Local SSL Setup
This project uses HTTPS for `localhost`. Before running Docker:
1. Generate your local certificates (e.g., using `mkcert`).
2. Save them in the `/certs` folder as `localhost.crt` and `localhost.key`.
3. Run `docker compose up -d --build`.