<p align="center">
  <img src="public/images/9mint.png" alt="9Mint logo" width="180">
</p>

---

## The 9Mint Team

<table>
  <thead>
    <tr>
      <th align="left">Name</th>
      <th align="left">Role</th>
      <th align="left">ID</th>
    </tr>
  </thead>
  <tbody>
    <tr><td><b>Arjan Singh</b></td><td>Project Lead &amp; Backend</td><td><code>240209768</code></td></tr>
    <tr><td><b>Dariusz Dabrowski</b></td><td>Backend Lead</td><td><code>240353669</code></td></tr>
    <tr><td><b>Jahirul Islam</b></td><td>Backend</td><td><code>240219893</code></td></tr>
    <tr><td><b>Khalil Suleiman</b></td><td>Backend</td><td><code>240248572</code></td></tr>
    <tr><td><b>Maliyka Liaqat</b></td><td>Frontend Lead</td><td><code>240119641</code></td></tr>
    <tr><td><b>Hamza Heybe</b></td><td>Frontend</td><td><code>240158042</code></td></tr>
    <tr><td><b>Naomi Olowu</b></td><td>Frontend</td><td><code>240229043</code></td></tr>
    <tr><td><b>Vlas Yermachenko</b></td><td>Backend &amp; NFT Artist</td><td><code>240180928</code></td></tr>
  </tbody>
</table>

---

## Project Summary

9Mint is a simulated e-commerce platform designed to sell and manage Non-Fungible Tokens (NFTs). The business domain falls within specialised digital asset retail, a growing area of the broader e-commerce sector.

**Scope & Key Features**
- **E-commerce Core:** A secure, fully functional online store with Customer and Admin roles.
- **NFT Inventory:** Robust management of Collections and NFTs, including edition and stock tracking.
- **Simulated Blockchain Ledger:** Native tracking of "on-chain" provenance, token ownership, and transactions.
- **Advanced Checkout & Payments:** Order lifecycle (cart → checkout → history) with simulated payment rails (Bank, Crypto, Platform Wallet).
- **Social & Community:** User friendships, direct messaging, NFT reviews, and seller profile feedback.
- **Modern UI:** A user-friendly interface combining Laravel Blade templates with interactive React 19 components (NFT Discovery Board, Marketplace Widgets) and Tailwind CSS.
- **Account Management:** Registration, login, profile customization, and wallet address management.

---

## Full local setup & docs

- **Local setup:** [docs/local-setup.md](docs/local-setup.md)
- **Troubleshooting / common fixes:** [docs/troubleshooting.md](docs/troubleshooting.md)
- **Dev Workflow (pull/push):** [docs/dev-workflow.md](docs/dev-workflow.md)
- **API overview (/api/v1):** [docs/api-overview.md](docs/api-overview.md)
- **Web flows (Blade pages, cart, orders, profile):** [docs/web-flows-overview.md](docs/web-flows-overview.md)

> Keep `.env`, `vendor/`, `node_modules/`, dumps out of Git. Commit migrations/seeders instead.
