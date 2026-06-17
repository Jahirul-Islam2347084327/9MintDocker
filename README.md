# Docker Node Mongo App 🐳

Welcome to my first successful, hands-on containerization project! This repository contains a simple Node.js and Express web server connected to a MongoDB database, orchestrated using Docker and Docker Compose.

## 🚀 Why I Built This
While navigating my DevOps journey, I initially attempted to dockerize a complex Laravel application. However, I quickly realized that trying to optimize a multi-service LEMP architecture without a firm grasp of container basics was causing massive imposter syndrome. 

Instead of burning out, I chose to strategically step back. I built this Node.js + MongoDB application to strip away the framework complexity and force myself to learn Docker's core fundamentals from scratch—no tutorials, just doing it and solving problems as they came.

---

## 🔧 Tech Stack
* **Backend Framework:** Node.js (Express)
* **Database:** MongoDB (Official Docker Image)
* **Orchestration:** Docker & Docker Compose

---

## 🧠 What I Learned (Mastering the Basics)
By stepping back from Laravel to this project, I finally solidified the foundational concepts I was missing:
* **Writing a Functional Dockerfile:** Understanding how to package a basic runtime environment.
* **Multi-Container Coordination:** Learning how to write a `docker-compose.yml` file to spin up an application layer and a database layer simultaneously.
* **Container Networking:** Realizing how containers communicate with each other internally versus how they expose ports to the host machine.
* **Environment Configuration:** Managing database connection strings cleanly using environment variables.

---

## 💥 Challenges I Faced & Key Takeaways
True learning happens when things break. Here are the real-world roadblocks I hit during this build and how I overcame them:

### 1. The "Localhost" Networking Trap
* **Problem:** My Node.js application kept failing to connect to MongoDB. I was trying to use `localhost:27017`.
* **Solution:** I learned that containers don't share `localhost` by default. I updated my connection string to use Docker's internal DNS resolution: `MONGO_URI=mongodb://mongo:27017` (where `mongo` matches the service name in my compose file).

### 2. The Infamous Build Typo
* **Problem:** Docker builds kept failing abruptly during the `npm install` phase.
* **Solution:** Discovered a tiny syntax typo: I was trying to copy `package*.jason` instead of `package*.json`. It taught me how strictly Docker adheres to filesystem paths.

### 3. Terminal Log Lock
* **Problem:** After running `docker-compose up`, my terminal completely locked up streaming logs, and I couldn't type any new commands.
* **Solution:** Learned the importance of the detached flag (`-d`), allowing me to run containers smoothly in the background while keeping my terminal free.

### 4. Database Security Defaults
* **Problem:** I wasn't sure if my database was automatically locked down with a password.
* **Solution:** Learned through documentation that the default official MongoDB image initializes without authentication unless explicitly configured via environment variables.

---

## 🐳 How to Run

> *Note: This project is currently in its initial, unoptimized stage. The goal here was execution and understanding networking basics, not advanced image optimization.*

### Prerequisites
Make sure you have [Docker](https://www.docker.com/) and [Docker Compose](https://docs.docker.com/compose/) installed on your machine.

### Steps
1. Clone this repository to your local machine.
2. Run the following command to build and launch the services:
```bash
   docker-compose up --build
