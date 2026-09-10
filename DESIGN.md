---
version: alpha
name: "SIMANTAP internal operations"
description: "Institutional work dashboard for persuratan, rapat, cuti, and Zona Integritas monitoring."
colors:
  primary: "#4f46e5"
  ink: "#0f172a"
  muted: "#64748b"
  surface: "#ffffff"
  canvas: "#f8fafc"
  border: "#e5e7eb"
  success: "#059669"
  warning: "#d97706"
  danger: "#dc2626"
typography:
  sans:
    fontFamily: "Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif"
  mono:
    fontFamily: "ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace"
rounded:
  DEFAULT: "0.75rem"
  sm: "0.625rem"
  md: "0.875rem"
  lg: "1.125rem"
spacing:
  section-gap: "1rem"
  page-max: "90rem"
components:
  button:
    rounded: "0.625rem"
    height: "2.375rem"
  card:
    rounded: "1rem"
    backgroundColor: "#ffffff"
    textColor: "#0f172a"
  badge:
    rounded: "999px"
    backgroundColor: "#eef2ff"
    textColor: "#4338ca"
---

# SIMANTAP Design System

## Overview

### Creative North Star

SIMANTAP is an institutional operations desk: calm white work surfaces, indigo wayfinding, and compact evidence blocks that make long administrative records easy to scan without losing their formal character.

### Product context and register

- **Audience and primary job:** PTA Papua Barat staff and leadership who manage correspondence, meetings, leave, approvals, and Zona Integritas evidence.
- **Target market(s) and evidence:** Internal Indonesian public-service administration; terminology and existing screens use Indonesian institutional language.
- **Locale(s) and language policy:** Indonesian UI and content; use natural Indonesian labels and preserve official names and titles.
- **Usage scene:** Frequent desktop use with responsive mobile access; records contain long names, titles, and status metadata.
- **Register:** Product/admin. Familiarity and information hierarchy take priority over decorative expression.
- **Memorable signature:** Indigo section eyebrows paired with compact, bordered metadata chips that turn dense operational context into scannable units.
- **Restraint:** Long names, legal text, and evidence details must wrap naturally; never hide required information behind hover-only affordances.
- **Anti-references:** Avoid marketing-style hero layouts, neon dashboards, decorative gradients that compete with records, and dense unstructured comma-separated metadata.
- **Token ownership/runtime mapping:** This file documents the existing Bootstrap-based Blade UI and its established indigo/slate tokens. Feature styles remain scoped to the owning Progress ZI views.

## Colors

Indigo `#4f46e5` marks navigation and selected states; ink `#0f172a` carries primary text; muted `#64748b` supports metadata; white and slate canvas surfaces separate work areas; borders provide structure without heavy shadows. Success, warning, and danger remain semantic and are paired with text or icons.

## Typography

Use the existing sans stack for Indonesian prose, controls, and names. Headings are strongly weighted with compact line-height; metadata is smaller but never below a readable size. Names and titles wrap rather than truncate because they are identity-bearing content. Italic is not used for ordinary UI labels.

## Layout

Progress ZI uses a responsive grid: grouped cards and side-by-side guide panels on wide screens, one column below 992px, and stacked member chips on narrow screens. Repeated areas share the same padding, border, radius, and gap rhythm. Long member names use flexible wrapping and `overflow-wrap:anywhere` so cards remain stable.

## Elevation & Depth

Hierarchy comes from white surfaces, thin borders, and restrained shadows (`rgba(15,23,42,.04)`). Selected cards may use a light indigo surface. Static data does not use dramatic elevation or blur.

## Shapes

Cards use 16–18px radii; controls and chips use 10–12px or pill radii where they represent a compact status. Dividers are one-pixel slate borders. Avatar/icon containers are compact rounded squares or circles and never replace the member's visible name.

## Components

### Foundational visual states

Interactive cards expose hover and visible keyboard focus. Selected area cards use a light indigo surface and stronger border. Empty, disabled, and error states retain the same footprint as their populated counterparts.

### Buttons and actions

Actions use the established Bootstrap button hierarchy with icon plus text where space permits. Destructive actions stay visually separate from neutral and primary actions.

### Navigation and data display

Area cards, progress rows, badges, and member lists are semantic, wrap-safe, and responsive. Member lists show each person as a discrete chip/row instead of one comma-separated paragraph.

### Forms and overlays

Existing Bootstrap form controls and modal patterns remain canonical. Labels and validation copy use Indonesian, with server-side validation preserved.

### Iconography

Font Awesome icons already used by the product provide compact semantic cues. Text labels remain present for important actions and data.

### Motion

Motion is limited to short hover/focus transitions on cards and controls. It communicates selection or affordance and should be removed under reduced-motion preferences.

### Content and data visualization

Use plain Indonesian action vocabulary and preserve official names exactly. Progress percentages and counts remain numeric and are paired with labels. Dense member metadata is represented as individually readable items.

## Do's and Don'ts

- **Do:** Keep long names visible, wrapped, and separated into individual members.
- **Do:** Reuse the same member-list treatment across Pedoman, Rekapan, and Monitoring.
- **Don't:** Render a full team as an unbroken comma-separated sentence.
- **Don't:** Solve overflow by clipping or relying on hover-only tooltips.
