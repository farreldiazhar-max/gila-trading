---
name: Pro-Data Glassmorphism
colors:
  surface: '#0f131c'
  surface-dim: '#0f131c'
  surface-bright: '#353943'
  surface-container-lowest: '#0a0e17'
  surface-container-low: '#181b25'
  surface-container: '#1c1f29'
  surface-container-high: '#262a34'
  surface-container-highest: '#31353f'
  on-surface: '#dfe2ef'
  on-surface-variant: '#c2c6d6'
  inverse-surface: '#dfe2ef'
  inverse-on-surface: '#2c303a'
  outline: '#8c909f'
  outline-variant: '#424754'
  surface-tint: '#adc6ff'
  primary: '#adc6ff'
  on-primary: '#002e6a'
  primary-container: '#4d8eff'
  on-primary-container: '#00285d'
  inverse-primary: '#005ac2'
  secondary: '#bec6e0'
  on-secondary: '#283044'
  secondary-container: '#3f465c'
  on-secondary-container: '#adb4ce'
  tertiary: '#4ae176'
  on-tertiary: '#003915'
  tertiary-container: '#00a74b'
  on-tertiary-container: '#003111'
  error: '#ffb4ab'
  on-error: '#690005'
  error-container: '#93000a'
  on-error-container: '#ffdad6'
  primary-fixed: '#d8e2ff'
  primary-fixed-dim: '#adc6ff'
  on-primary-fixed: '#001a42'
  on-primary-fixed-variant: '#004395'
  secondary-fixed: '#dae2fd'
  secondary-fixed-dim: '#bec6e0'
  on-secondary-fixed: '#131b2e'
  on-secondary-fixed-variant: '#3f465c'
  tertiary-fixed: '#6bff8f'
  tertiary-fixed-dim: '#4ae176'
  on-tertiary-fixed: '#002109'
  on-tertiary-fixed-variant: '#005321'
  background: '#0f131c'
  on-background: '#dfe2ef'
  surface-variant: '#31353f'
  bullish: '#22c55e'
  bearish: '#ef4444'
  warning: '#f59e0b'
  text-primary: '#e2e8f0'
  text-muted: '#94a3b8'
  glass-surface: rgba(15, 23, 42, 0.8)
  border-subtle: rgba(148, 163, 184, 0.1)
typography:
  headline-lg:
    fontFamily: Inter
    fontSize: 32px
    fontWeight: '700'
    lineHeight: '1.2'
    letterSpacing: -0.02em
  headline-md:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '600'
    lineHeight: '1.3'
  headline-sm:
    fontFamily: Inter
    fontSize: 20px
    fontWeight: '600'
    lineHeight: '1.4'
  body-lg:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: '1.6'
  body-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: '1.5'
  data-mono:
    fontFamily: JetBrains Mono
    fontSize: 14px
    fontWeight: '500'
    lineHeight: '1.4'
  label-caps:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '700'
    lineHeight: '1'
    letterSpacing: 0.05em
  status-badge:
    fontFamily: Inter
    fontSize: 11px
    fontWeight: '800'
    lineHeight: '1'
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  unit: 4px
  gutter: 16px
  margin-page: 24px
  table-row-height: 40px
  card-padding: 20px
---

## Brand & Style

The design system is engineered for **SahamPintar**, a platform where precision, speed, and analytical depth are paramount. The brand personality is **Professional, Technical, and Authoritative**, mirroring the high-stakes environment of financial markets. It seeks to evoke a sense of "Expert Control," providing users with the tools of a professional trading floor through a refined, modern interface.

The visual direction utilizes **Glassmorphism** layered over a **Minimalist** dark foundation. This approach ensures that high-density data remains legible without feeling overwhelming. By using semi-transparent surfaces and blurred backgrounds, the UI maintains a sense of spatial depth that organizes complex financial information into manageable visual tiers. The aesthetic is "Bloomberg-modern"—functional, dark-mode first, and hyper-focused on data visualization.

## Colors

The palette is anchored by a **Deep Dark Navy** (#0a0e17), providing a low-strain environment for long-duration technical analysis. 

- **Primary Accent**: Electric Blue (#3b82f6) is reserved for interactive elements, primary CTAs, and active navigation states.
- **Semantic Logic**: The system adheres to strict financial color conventions. Emerald Green (#22c55e) represents bullish signals, profit, and "Buy" actions. Crimson Red (#ef4444) represents bearish signals, losses, and "Sell" actions. Amber (#f59e0b) is used for "Hold" or neutral warnings.
- **Surface Strategy**: Most containers use a glassmorphic fill (`glass-surface`) to create a sense of elevation over the deep navy base. 
- **Typography Contrast**: Primary text uses high-contrast slate-whites (#e2e8f0), while secondary metadata uses muted blue-grays (#94a3b8) to reduce visual noise.

## Typography

This design system uses **Inter** for all UI and prose elements due to its exceptional legibility in dark environments and varied weights. For financial data, ticker symbols, and R/R ratios, **JetBrains Mono** is utilized to ensure that numerical values align vertically in tables, facilitating easier comparison of digits.

On mobile devices, `headline-lg` scales down to 24px (`headline-md`) to ensure charts and ticker headers remain within the viewport. All data-heavy labels use uppercase with slight tracking (letter-spacing) to distinguish them from body content.

## Layout & Spacing

The system follows a **12-column fluid grid** for dashboard layouts, allowing widgets (charts, news feeds, heatmaps) to resize dynamically. 

- **Data Density**: To maximize information density, a 4px base unit is used. Table rows are kept to a compact 40px height to allow more tickers to be visible above the fold.
- **Sidebar Architecture**: A fixed 240px sidebar houses navigation, while the main content area utilizes a fluid width with 24px outer margins.
- **Responsive Behavior**: On tablets, the grid collapses to 6 columns. On mobile, the sidebar transitions to a bottom navigation bar or a hamburger menu, and all content spans 1 column with 16px horizontal padding.

## Elevation & Depth

Depth is established through **Backdrop Filters** and **Tonal Layering** rather than traditional shadows, which can appear muddy in deep dark themes.

1.  **Level 0 (Base)**: `#0a0e17` - The canvas for all content.
2.  **Level 1 (Surface)**: `rgba(15, 23, 42, 0.8)` with a `backdrop-filter: blur(12px)`. These are the primary cards for charts and tables.
3.  **Level 2 (Interactive)**: Hover states on cards use a subtle highlight border `rgba(59, 130, 246, 0.3)` and a slight increase in opacity to `0.9`.
4.  **Level 3 (Overlays)**: Tooltips and dropdown menus use a solid `#1e293b` background to ensure absolute contrast against the glass layers below.

Borders are strictly `1px solid rgba(148, 163, 184, 0.1)`, acting as "ghost outlines" that define structure without adding visual bulk.

## Shapes

The design system employs a **Soft** roundedness level (0.25rem / 4px) to maintain a professional, slightly technical edge. While fully sharp corners feel dated, overly rounded corners (pill-shaped) detract from the serious, data-driven nature of financial analysis.

- **Buttons & Inputs**: 4px radius.
- **Cards**: 8px (rounded-lg) for the main dashboard containers to provide a gentle containerization of data.
- **Status Badges**: 2px radius for a "tag-like" appearance that fits within tight table rows.

## Components

### Buttons & Controls
- **Primary Button**: Solid Electric Blue (#3b82f6) with white text. 4px radius.
- **Secondary/Ghost**: Transparent fill with a `border-subtle` and `#e2e8f0` text.
- **Semantic Buttons**: "Buy" buttons use a solid Bullish Green; "Sell" buttons use Bearish Red.

### Data Tables
- **Header**: `label-caps` typography, muted text, bottom-only border.
- **Rows**: Alternating subtle zebra striping (optional) or simple `border-subtle` separators.
- **Numeric Cells**: Always use `data-mono` typography and right-alignment for easier comparison.

### Cards
- Glassmorphic background. 1px `border-subtle`. 
- Header section within cards should have a dedicated bottom border and 12px padding.

### Status Badges
- High-contrast, small text (`status-badge`).
- **Strong Buy**: Dark green background with bright green text.
- **Strong Sell**: Dark red background with bright red text.

### Input Fields
- Background: `rgba(15, 23, 42, 0.5)`.
- Border: `rgba(148, 163, 184, 0.2)`.
- Focus State: Border color shifts to Electric Blue with a subtle 2px outer glow.