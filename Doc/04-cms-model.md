# CMS Model

## Philosophy

The CMS is structured. Admin users assemble approved sections and components within controlled templates instead of building arbitrary layouts.

## Main Concepts

### Templates

Templates define page structure and allowed areas.

### Sections

Sections are reusable composition units such as hero banners, product lists, promo strips, and rich text blocks.

### Components

Components are smaller elements nested inside sections when a section supports them.

### Preview and Publish

- draft records remain editable
- preview renders draft state
- publish captures a version snapshot and updates public state

## Validation Rules

- JSON settings must be schema-backed
- section and component definitions must declare validation rules
- supported data sources must be explicit
- layout freedom is constrained by template and section contracts
