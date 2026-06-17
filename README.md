# Docker Laravel & Node App 🐳

Welcome to my first hands-on containerization project! This repository contains a Laravel web application integrated with Node.js (for frontend asset compilation) and connected to a MySQL database, with the entire environment orchestrated using Docker and Docker Compose.

## 🚀 Why I Built This
After learning the theoretical concepts of DevOps, I wanted to challenge myself by containerizing a real-world stack. Instead of choosing a simple static app, I went straight into a full-stack Laravel application that relies on Node.js for its frontend and MySQL for its data layer. 

I chose to build this environment piece-by-piece from scratch—no hand-holding tutorials—solving problems independently as they came up.

---

## 🔧 Tech Stack
* **Backend Framework:** Laravel (PHP)
* **Frontend Build Tool:** Node.js (Vite / Asset Compilation)
* **Database:** MySQL (Official Docker Image)
* **Orchestration:** Docker & Docker Compose

---

## 🧠 What I Learned (Mastering the Basics)
Building this multi-layered environment helped me bridge the gap between local development and containerized architectures. Through this project, I mastered the core basics:
* **Writing a Functional Dockerfile:** Understanding how to package a PHP runtime environment alongside its necessary system extensions (like `pdo_mysql` and `zip`).
* **Multi-Container Coordination:** Learning how to write a `docker-compose.yml` file to spin up the application layer and a MySQL database layer simultaneously.
* **Container Networking:** Realizing how containers communicate with each other internally versus how they expose ports to the host machine.
* **Environment Configuration:** Managing database connection strings cleanly using Laravel's `.env` setup inside a container network.

---

## 💥 Challenges I Faced & Key Takeaways
True learning happens when things break. Here are the real-world roadblocks I hit during this build and how I overcame them:

### 1. The "Localhost" Networking Trap
* **Problem:** My Laravel application kept failing to connect to MySQL, throwing connection errors when using `localhost:3306`.
* **Solution:** I learned that containers don't share `localhost` by default. I updated my environment configuration to use Docker's internal DNS resolution: `DB_HOST=mysql` (matching the service name defined in my compose file).

### 2. The Infamous Build Typo
* **Problem:** Docker builds kept failing abruptly during the package installation phase.
* **Solution:** Discovered a tiny syntax typo: I was trying to copy `package*.jason` instead of `package*.json`. It taught me how strictly Docker adheres to filesystem paths.

### 3. Terminal Log Lock
* **Problem:** After running `docker-compose up`, my terminal completely locked up streaming logs, preventing me from running any migrations or commands.
* **Solution:** Learned the importance of the detached flag (`-d`), allowing me to run the Laravel app and MySQL smoothly in the background while keeping my terminal free.

### 4. Database Security Defaults
* **Problem:** I wasn't sure how to safely handle the database credentials and initialization.
* **Solution:** Learned through documentation how the official MySQL image initializes databases and root passwords via environment variables passed through Docker Compose.

---

## 🐳 How to Run

> *Note: This project is currently in its initial, unoptimized stage. The goal here was executing a complex multi-tech stack and understanding networking basics, not advanced image optimization.*

### Prerequisites
Make sure you have [Docker](https://www.docker.com/) and [Docker Compose](https://docs.docker.com/compose/) installed on your machine.

### Steps
1. Clone this repository to your local machine.
2. Run the following command to build and launch the services:
   ```bash
   docker-compose up --build
