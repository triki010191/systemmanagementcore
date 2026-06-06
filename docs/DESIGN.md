---
name: Network Operations Interface
colors:
  surface: '#f7f9fb'
  surface-dim: '#d8dadc'
  surface-bright: '#f7f9fb'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f2f4f6'
  surface-container: '#eceef0'
  surface-container-high: '#e6e8ea'
  surface-container-highest: '#e0e3e5'
  on-surface: '#191c1e'
  on-surface-variant: '#424656'
  inverse-surface: '#2d3133'
  inverse-on-surface: '#eff1f3'
  outline: '#737687'
  outline-variant: '#c2c6d9'
  surface-tint: '#0053da'
  primary: '#004cca'
  on-primary: '#ffffff'
  primary-container: '#0062ff'
  on-primary-container: '#f3f3ff'
  inverse-primary: '#b4c5ff'
  secondary: '#565e74'
  on-secondary: '#ffffff'
  secondary-container: '#dae2fd'
  on-secondary-container: '#5c647a'
  tertiary: '#48586d'
  on-tertiary: '#ffffff'
  tertiary-container: '#607087'
  on-tertiary-container: '#eef3ff'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#dbe1ff'
  primary-fixed-dim: '#b4c5ff'
  on-primary-fixed: '#00174b'
  on-primary-fixed-variant: '#003ea8'
  secondary-fixed: '#dae2fd'
  secondary-fixed-dim: '#bec6e0'
  on-secondary-fixed: '#131b2e'
  on-secondary-fixed-variant: '#3f465c'
  tertiary-fixed: '#d3e4fe'
  tertiary-fixed-dim: '#b7c8e1'
  on-tertiary-fixed: '#0b1c30'
  on-tertiary-fixed-variant: '#38485d'
  background: '#f7f9fb'
  on-background: '#191c1e'
  surface-variant: '#e0e3e5'
typography:
  display-lg:
    fontFamily: Inter
    fontSize: 36px
    fontWeight: '700'
    lineHeight: 44px
    letterSpacing: -0.02em
  headline-md:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
    letterSpacing: -0.01em
  headline-sm:
    fontFamily: Inter
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  body-lg:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  body-sm:
    fontFamily: Inter
    fontSize: 13px
    fontWeight: '400'
    lineHeight: 18px
  mono-data:
    fontFamily: JetBrains Mono
    fontSize: 13px
    fontWeight: '500'
    lineHeight: 18px
  label-caps:
    fontFamily: Inter
    fontSize: 11px
    fontWeight: '700'
    lineHeight: 16px
    letterSpacing: 0.05em
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  base: 4px
  xs: 4px
  sm: 8px
  md: 16px
  lg: 24px
  xl: 32px
  sidebar_width: 260px
  container_max: 1600px
  gutter: 20px
---

## Brand & Style

The design system is engineered for high-stakes enterprise environments where clarity, speed, and precision are paramount. The brand personality is **authoritative, technical, and hyper-efficient**, mirroring the reliability of fiber-optic infrastructure. 

The aesthetic follows a **Corporate/Modern** direction with a functional split: a deep-toned navigation environment for focused task-switching and a high-clarity, light-themed workspace for data analysis. It prioritizes information density without sacrificing legibility, utilizing subtle depth cues and a refined color hierarchy to guide the user’s eye to critical system alerts and performance metrics.

## Colors

This design system utilizes a specialized "split-surface" palette. 

- **Primary (Telco Blue):** Used for primary actions, active states, and brand presence. It is a high-vibrancy blue optimized for legibility against both light and dark backgrounds.
- **Surface Strategy:** The application uses a **Deep Navy/Charcoal** (`#0F172A`) for the global sidebar to recede visually, while the main stage uses a **Clean Gray/White** (`#F8FAFC`) to maximize contrast for data-heavy tables.
- **Status Triage:** Given the network management context, semantic colors (Success, Warning, Danger) use "Safety" tones that are color-blind accessible and high-visibility to ensure critical outages are never missed.

## Typography

The typography system relies on **Inter** for its exceptional legibility in technical interfaces and high-density layouts. 

- **Data Density:** `body-md` and `body-sm` are the workhorse sizes for data tables. 
- **Monospacing:** For IP addresses, MAC addresses, and throughput values, the system introduces a secondary monospaced font (JetBrains Mono) to ensure character alignment and prevent "jumping" during real-time data updates.
- **Hierarchy:** We use `label-caps` for table headers and section metadata to provide clear structural scaffolding without competing with the primary data points.

## Layout & Spacing

The layout is a **hybrid fixed-fluid model**. The sidebar remains fixed at `260px` to provide a consistent navigation anchor, while the content area fluidly expands up to a `1600px` max-width to accommodate multi-column data dashboards.

A strict **4px base grid** is used to maintain a "dense but breathable" feel. Tables and lists should utilize `sm` (8px) vertical padding to maximize the number of visible rows on a standard 1080p monitor. Gutters are set to `20px` to create distinct separation between modular cards and visualization panels.

## Elevation & Depth

Depth in the design system is communicated through **Tonal Layering** rather than heavy shadows.

- **Level 0 (Background):** The canvas uses `#F1F5F9` (Slate 100) to create a soft foundation.
- **Level 1 (Cards/Worksheets):** Primary content containers are pure white with a thin `1px` border in Slate 200. This "flat-depth" approach reduces visual noise in complex layouts.
- **Level 2 (Interaction/Popovers):** Context menus and tooltips use a soft, ambient shadow (`0 10px 15px -3px rgba(0,0,0,0.1)`) to pull the user's focus during configuration tasks.
- **Active State:** Selected items in the sidebar or data table use a "Left-Border Trace"—a 3px solid primary blue line—to denote focus without needing to change background colors drastically.

## Shapes

The design system employs a **Soft (4px)** corner radius. This choice balances the professional, rigid nature of telecommunications hardware with the approachability of modern SaaS software.

- **Buttons & Inputs:** Use the standard `rounded` (4px) to maintain a crisp, tool-like appearance.
- **Status Badges:** Use a "Pill" shape (9999px) to clearly differentiate categorical metadata (like "Online" or "Offline") from interactive components.
- **Data Visualizations:** Nodes in network maps should use circular geometry to represent connection points, contrasting against the rectangular nature of the UI.

## Components

### Data Tables
Tables are the core of the system. They must feature:
- **Sticky Headers:** Always visible when scrolling.
- **Zebra Striping:** Sublte Slate-50 fills for alternate rows.
- **Condensed Row Height:** 40px height for maximum information density.

### Buttons
- **Primary:** Solid Telco Blue with white text. No gradients.
- **Secondary/Ghost:** Slate-200 border with Slate-700 text.
- **Critical:** Solid Danger Red for destructive actions like "Reset Port."

### Input Fields
Inputs must have a defined **Focus State** using a 2px Primary Blue ring. Labels should be positioned above the input in `label-caps` for clarity.

### Status Indicators
Small circular dots or pill-shaped badges.
- **Green (Pulse):** Active connection.
- **Amber (Static):** High latency/Warning.
- **Red (Flash):** Connection lost.

### Network Cards
Summary cards at the top of the dashboard should feature a "Sparkline" visualization showing 24h throughput trends, utilizing the primary brand color for the stroke.