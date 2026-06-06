# HFNMS Development Rules

You are assisting development of the **Hinet Fiber Network Management System**.

## Mandatory Rules

1. Never change project architecture without approval.
2. Always follow `docs/PROJECT_OVERVIEW.md`, `docs/NETWORK_ENGINEERING_RULES.md`, `docs/DATABASE_DESIGN.md`, and `docs/ARCHITECTURE.md`.
3. Prioritize maintainability, scalability, and clean code.
4. Use Laravel 13 best practices.
5. Use Repository Pattern when needed.
6. Use Service Layer for business logic.
7. Use Form Request Validation.
8. Use Eloquent Relationships correctly.
9. Use TailwindCSS and AlpineJS.
10. Avoid unnecessary complexity.
11. Every database migration must be documented in `docs/migrations/`.
12. Every model must contain clear relationships.
13. Every feature must support future Fiber Path Tracing.
14. Never create duplicate data structures.
15. GIS Map is a core feature.
16. Fiber Core Management is a core feature.
17. Tube Color Management is a core feature.
18. Customer-to-Core relationship must always be traceable.
19. All network assets must support GPS coordinates.
20. All network assets must support QR Code tracking.

## Network Hierarchy

```
POP → OLT → OTB → ODC → Splitter → ODP → Customer
```

## Code Generation Order

1. Explain architecture first
2. Explain database structure first
3. Generate migrations before models
4. Generate models before controllers
5. Generate services before UI
6. Generate tests whenever possible

## When Unsure

Ask questions before generating code. Never assume network engineering rules. Always preserve data integrity.

## Primary Objective

**END-TO-END FIBER PATH TRACING** — every code decision must support this.

## Stitch Design Reference

Screen assets: `design/stitch/` (fetch via `scripts/fetch-stitch-assets.mjs`)
Design tokens: `docs/DESIGN.md`
