# Coding Standards

## General

- keep controllers thin
- use services and actions for workflows
- prefer explicit contracts
- keep package boundaries clean
- avoid business logic in Blade templates
- keep JSON config schema-backed

## Naming

- keep custom package names neutral
- keep namespaces neutral
- avoid upstream vendor naming in product-facing docs and labels

## Validation

- centralize validation per resource or definition type
- validate JSON settings before persistence
- encode schema and default config together where possible

## Testing

- prefer feature tests for workflows
- add render tests for storefront slices
- add schema validation tests for sections/components

## Admin UI

- use the shared admin modal pattern for all admin dialogs
- prefer `<x-admin::modal>` for standard admin modals so the shared backdrop, surface, header, body, footer, and close button styles are inherited
- use the `admin-modal-*` classes for custom JavaScript-driven admin dialogs that cannot use `<x-admin::modal>`
- keep modal changes presentation-only unless a workflow explicitly requires behavior changes
