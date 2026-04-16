# Design System Document: The Monolith & The Muse

## 1. Overview & Creative North Star
**The Creative North Star: "Architectural Editorial"**
This design system moves away from the "template" nature of e-commerce to embrace the soul of a high-fashion print magazine. It is built on the tension between **The Monolith** (strong, black, geometric forms) and **The Muse** (ethereal whitespace, delicate typography, and tonal shifts). 

To achieve this, we reject standard UI patterns. We replace 1px borders with tonal shifts; we replace shadows with structural layering; and we replace "loud" CTAs with quiet, confident authority. The goal is an experience that feels "curated," not "calculated."

---

## 2. Colors & Tonal Logic
Our palette is a study in monochromatic restraint. We rely on the Material Design convention of surface tiers to create depth without visual noise.

### The "No-Line" Rule
**Explicit Instruction:** Do not use `1px` solid borders to divide sections. Visual separation must be achieved through background shifts. A section using `surface-container-low` (#F5F3F2) sitting against the main `surface` (#FCF9F8) creates a cleaner, more premium boundary than a line ever could.

### Surface Hierarchy & Nesting
Treat the interface as a physical stack of fine stationery:
- **Surface (Base):** #FCF9F8 – The canvas.
- **Surface-Container-Low:** #F5F3F2 – Sub-sections or secondary content blocks.
- **Surface-Container-Highest:** #E3E2E1 – Utility bars, search overlays, or active selection states.

### Signature Textures & Glassmorphism
To break the "flatness" of digital design, use **Glassmorphism** for floating elements like Sticky Headers or Quick-View modals. 
- **Effect:** Apply `backdrop-blur-xl` with a background of `surface/80`.
- **The "Soul" Gradient:** For main Hero sections, use a negligible radial gradient from `primary` (#121212) to `primary-dim` (#535252) to prevent the black from looking "dead" on OLED screens.

---

## 3. Typography: The Editorial Voice
Typography is our primary vehicle for brand expression. The pairing of **Epilogue** (Headings) and **Inter** (Body) creates an authoritative yet modern dialogue.

- **Display-LG (64px / 1.1 / -0.02em):** Used for "Hero Moments." These should often be intentionally oversized to bleed off the grid or overlap images, creating an asymmetric, editorial feel.
- **Headline-MD (1.75rem / 1.2):** Used for product category titles.
- **Body-LG (1rem / 1.6):** High line-height is mandatory. Inter needs room to breathe to maintain the "Quiet Luxury" feel.
- **Label-MD (0.75rem / Uppercase / 0.15em tracking):** The "Curator’s Mark." Use this for micro-copy, breadcrumbs, and metadata. The wide tracking is essential for the premium aesthetic.

---

## 4. Elevation & Depth: Tonal Layering
We move beyond the "Drop Shadow" era. Depth is now a matter of **Atmospheric Perspective.**

- **The Layering Principle:** Place a `surface-container-lowest` (#FFFFFF) card on a `surface-container-low` (#F5F3F2) background. This creates a "lift" that feels organic and structural.
- **Ambient Shadows:** When a float is required (e.g., a cart drawer), use a shadow color derived from `on-surface` (#313332) at 4% opacity with a 32px blur. It should look like a soft glow, not a shadow.
- **The "Ghost Border" Fallback:** If a border is required for accessibility, use `outline-variant` (#B2B2B1) at **15% opacity**. High-contrast borders are strictly forbidden.

---

## 5. Components: Architectural Primitives

### Buttons
- **Primary:** Background `primary` (#121212), Text `on-primary` (#FAF7F6), Radius `0px`. Padding: `16px 32px`. 
- **Secondary:** Transparent background, `1px` border of `primary` (#121212), Radius `0px`.
- **Interaction:** On hover, the Primary button should shift to `primary-dim` (#535252). No "pop" animations; use slow, 400ms linear transitions.

### Input Fields
- **Editorial Style:** Remove all borders except the bottom. 
- **Base:** `border-b` using `outline-variant`.
- **Focus:** `border-b-2` using `primary`. 
- **Placeholder:** `muted` (#737373) in `label-sm` style.

### Cards & Product Grids
- **Forbid Dividers:** Do not use lines between products. Use the Spacing Scale (`gap-16` or `gap-24`) to create separation.
- **Asymmetric Layout:** Encourage "Broken Grid" layouts where product images vary slightly in aspect ratio (e.g., 4:5 for portraits, 1:1 for close-ups).
- **Metadata:** Product info must be left-aligned, using `body-md` for names and `title-sm` for pricing.

### Editorial Badges
- **Style:** Rectangular (`0px` radius). Background `secondary-container` (#E5E2E1), Text `on-secondary-container`. Use for "New In" or "Limited Edition."

---

## 6. Do’s and Don'ts

### Do
- **DO** use massive amounts of whitespace. If you think there is enough space, double it.
- **DO** use `0px` radius for a brutalist, architectural feel. Reserve `4px` only for interactive elements like checkboxes.
- **DO** overlap elements. Let a heading sit slightly over an image to create depth.

### Don't
- **DON'T** use 100% black text (#000) on white. Use `on-surface` (#313332) to keep the "Quiet Luxury" softness.
- **DON'T** use standard "Hover" states like brightening a color. Only use tonal shifts or subtle scale transitions (102%).
- **DON'T** use icons for everything. Often, a text label in `label-md` uppercase is more premium than a generic SVG icon.

---

## 7. Tailwind Configuration (Implementation)