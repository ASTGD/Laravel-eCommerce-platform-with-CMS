# AGENTS.md

## Purpose

This package owns storefront presentation for the default theme implementation.

Use this package for:

- storefront layouts
- storefront sections
- storefront partials
- component presentation
- responsive behavior
- accessibility
- visual polish
- Figma-driven storefront UI work

## Rules

- Do not change commerce core behavior.
- Do not redesign the full Bagisto admin panel here.
- Do not move business logic into Blade templates.
- Do not introduce ad hoc one-off page logic.
- Do not break CMS rendering contracts.
- Do not bypass theme-core contracts or render pipeline rules.
- Keep changes functional, maintainable, and driven by approved storefront design context.

## Workflow

- Treat approved Figma Make designs as the visual source of truth for storefront work.
- Implement visual changes in this package after the backend workspace has defined the required contracts and data flow.
- Use shared rendering contracts from `packages/theme-core`.
- Keep the default theme focused on presentation, not architecture.
