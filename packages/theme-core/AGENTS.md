# AGENTS.md

## Purpose

This package owns shared theme-layer contracts, rendering primitives, and preset resolution.

Use this package for:

- section renderer contracts
- component renderer contracts
- theme preset resolution
- shared theme view models and helpers
- common rendering rules for the storefront layer

## Rules

- Do not change commerce core behavior.
- Do not redesign the admin panel here.
- Do not put business logic in Blade templates.
- Do not add one-off page logic for individual pages.
- Do not weaken CMS rendering contracts.
- Keep contracts explicit, reusable, and backward-aware.
- Keep visual experimentation out of this package; `packages/theme-default` owns the concrete storefront presentation.

## Workflow

- Backend workspace changes should define new contracts or data flow before this package expands them.
- Use this package to keep render behavior predictable and theme-driven.
- Treat approved storefront design decisions as implementation constraints, not as a license to create new application architecture.
