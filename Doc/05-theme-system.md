# Theme System

## Principles

- one theme core
- one default theme
- multiple presets
- configuration-driven visual variation

## Token Categories

- colors
- typography
- spacing
- border radius
- shadows
- button styles
- card styles
- grid spacing
- section spacing
- header/footer styles

## Render Layers

1. CMS resolves page structure.
2. Commerce data sources resolve content where needed.
3. Theme preset resolver selects active tokens.
4. Theme default Blade views render the page.

## Variant Strategy

Variation must come from preset config, section/component variants, and style tokens rather than client-specific code forks.
