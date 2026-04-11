# Backend Pre-Frontend Checklist

## Purpose

This checklist captures the remaining backend-only work that is worth doing before storefront-focused frontend implementation begins.

It is intentionally limited to hardening and operational clarity. The backend architecture for homepage, category page, and product page CMS composition should now be treated as stable unless a real frontend implementation gap is discovered.

## Priority Order

1. ACL hardening
2. regression test expansion
3. local and dev install workflow cleanup
4. SEO and media backend hardening
5. queue, scheduler, and runtime verification
6. admin debranding completion
7. demo seed and sample data quality

## Item 1: ACL Hardening

Status: in progress and now partially completed.

Completed in this pass:

- added explicit permission middleware for custom CMS and theme admin routes
- aligned custom create and store routes to the same permission keys
- aligned custom edit, update, and preview routes to the same permission keys
- kept authorization inside the Bagisto admin permission model instead of introducing a second ACL system

Still worth doing under ACL hardening:

- verify menu visibility stays consistent for custom roles across all custom screens
- define and seed recommended non-super-admin roles for internal demos and QA
- add more route-level permission tests for assignments, content entries, site settings, header configs, and footer configs

## Item 2: Regression Test Expansion

Add or extend:

- permission tests for CMS and theme admin routes
- regression tests for assignment precedence
- regression tests for preview and publish workflows
- regression tests for restore semantics
- regression tests for shared site settings and content entry payload resolution

Goal:

- frontend can build against backend behavior that is already locked by tests

## Item 3: Local And Dev Install Workflow Cleanup

Confirm and document one clean local path using Sail:

- install
- migrate
- seed
- create admin
- run queue worker if needed
- run scheduler if needed
- run Vite

Goal:

- no ambiguity when bringing the project up on a fresh machine

## Item 4: SEO And Media Backend Hardening

Confirm and tighten:

- SEO default and override behavior
- canonical and slug expectations
- media attachment rules for CMS-managed content
- alt text and structured media payload expectations

Goal:

- frontend does not discover backend content problems late

## Item 5: Queue, Scheduler, And Runtime Verification

Verify and document:

- which features depend on queue workers
- which scheduled commands matter in local and production environments
- which runtime services are optional versus required during development

Goal:

- no hidden operational dependency surprises

## Item 6: Admin Debranding Completion

Finish remaining client-facing admin branding cleanup where needed:

- favicon and tab branding
- framework-identifying response headers if required
- any remaining visible upstream vendor marks in client-visible admin surfaces

Goal:

- safe client demos from the admin side

## Item 7: Demo Seed And Sample Data Quality

Keep one stable sample dataset for:

- homepage
- category page
- product page
- content entries
- theme presets

Goal:

- frontend can work against predictable payloads instead of empty or inconsistent state

## What Not To Do Before Frontend

Do not use this checklist as a reason to reopen backend architecture work.

Avoid:

- new CMS abstraction layers
- new page-builder flexibility
- speculative data contracts
- broad admin redesign
- frontend-driven backend changes without a real implementation need
