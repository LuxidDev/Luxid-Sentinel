<p align="center">
    <img src="https://luxid.dev/lion5.svg" width="120" alt="Luxid Logo">
</p>

<p align="center">
    <strong>Luxid Engine</strong><br>
    A lightweight, expressive PHP framework for developers who value clarity and control.
</p>

<p align="center">
    ⚠️ <strong>Pre-release:</strong> APIs are unstable and subject to change.
</p>

---

## About Luxid

> **Note:** This repository contains the core engine of the Luxid framework.
> If you want to build an application using Luxid, starter templates and documentation will be provided in the future.

**Luxid** is a modern, lightweight PHP framework designed with simplicity, speed, and architectural clarity in mind.

Rather than enforcing heavy abstractions, Luxid gives developers full control over how their applications are structured while still providing powerful tools for routing, request handling, and rendering.

Luxid introduces a clean architectural pattern called **SEA**:

> **Screen (Views) → Entities (Models) → Actions (Controllers)**

This structure keeps applications readable, maintainable, and easy to reason about—especially for developers interested in understanding framework internals.

---

## Why Luxid?

Web development should feel clear and enjoyable, not overwhelming.

Luxid focuses on:

- Explicit and expressive routing
- Action-based request handling
- A simple, readable rendering system
- Minimal setup with maximum flexibility
- Transparent internals with no hidden magic

Luxid removes unnecessary complexity found in larger frameworks while preserving the features developers rely on daily.

---

## Key Features

- Fast and expressive routing engine
- Action-based controllers (a cleaner alternative to classical MVC)
- Lightweight Screen rendering system using `.nova.php`
- Framework-level request sanitization
- Extensible and modular architecture
- Elegant and readable syntax
- Zero-dependency core (Composer autoloading only)

---

## Use Cases

Luxid is well suited for:

- Small to medium-sized web applications
- APIs and backend services
- Dashboards and admin panels
- School and campus management systems
- Learning and teaching modern PHP framework design

---

## Learning Luxid

Luxid is intentionally designed to be beginner-friendly, especially for developers learning how frameworks work internally.

Documentation and guides will be published soon in the `/docs` directory.

Until then, you can explore the core structure:

- `screens/` — application views and UI logic
- `actions/` — request handlers and controllers
- `entities/` — domain models
- `system/` — framework core (Router, Request, Response, Engine)

---

## Contributing

Thank you for considering contributing to Luxid.

Contribution guidelines will be included in the documentation. In general:

- Follow PSR-12 coding standards
- Submit pull requests with clear descriptions
- Ensure new features are documented and tested

---

## Security Vulnerabilities

If you discover a security vulnerability within Luxid, please report it responsibly.

**Email:** jhay@luxid.dev

All reports will be reviewed and addressed promptly.

---

## License

Luxid is open-source software licensed under the **MIT License**.
