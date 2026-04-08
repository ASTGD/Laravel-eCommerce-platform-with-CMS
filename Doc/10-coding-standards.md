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
