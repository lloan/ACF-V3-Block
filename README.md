# CTC Comparison Table (ACF Block) – Reference Implementation

A WordPress block built with ACF (Advanced Custom Fields) V3 to display comparison tables with company information, ratings, fees, and call-to-action buttons.

This was originally implemented as a **timed technical assessment for a Senior WordPress Developer role** and is published here as a **portfolio piece / reference implementation**, not as a polished, widely-distributed plugin.

![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple)
![Status](https://img.shields.io/badge/Type-Assessment%20%2F%20POC-informational)

## 🧩 Context & Goals

- **Context**: Timed take‑home assessment for a company hiring a Senior WordPress Developer.
- **Goal**: Demonstrate senior‑level thinking around:
  - Block development with ACF V3
  - Theme‑friendly, responsive UI
  - Accessibility and semantics
  - Sensible data modelling (ACF field group)
  - Clean, maintainable PHP/CSS structure
- **Scope**: Focused on the core experience rather than a full product:
  - No settings pages or admin UX beyond the block itself
  - No localization/i18n polish beyond basic text domain
  - No automated tests (manual verification only)
  - Not published to the WordPress.org plugin repo

## ✨ Features

- **ACF V3 Block Integration**: Seamlessly integrates with WordPress Block Editor
- **Responsive Design**: Card-based mobile layout (instead of scrollable tables) for better UX - see [Design Philosophy](#-responsive-design) below
- **Flexible Content**: Display company logos, ratings, fees, experience, licenses, and more
- **Smart CTAs**: Intelligent button priority system (Request Intro → Reviews → Website)
- **Accessibility**: Built with ARIA attributes and semantic HTML for screen readers
- **Customizable**: Easy to theme with CSS custom properties
- **Performance**: Assets only load when the block is present on the page

## 📋 Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- [Advanced Custom Fields Pro](https://www.advancedcustomfields.com/pro/) plugin (ACF V3 required)

## 🚀 Running the Example Locally

This section is mainly for reviewers or anyone who wants to see the block in a real WordPress environment.

### Method 1: Manual Installation

1. Download or clone this repository
2. Upload the `ctc-comparison-table` folder to `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Ensure ACF Pro is installed and activated

### Method 2: Git Clone

```bash
cd wp-content/plugins
git clone https://github.com/yourusername/acf-comparison-table.git ctc-comparison-table
```

## 📖 Usage

### Adding the Block

1. Edit any post or page in the WordPress Block Editor
2. Click the "+" button to add a new block
3. Search for "CTC Comparison Table"
4. Add the block to your page

### Configuring the Block

The block includes the following fields:

#### Component Settings
- **Component Title**: Main heading for the comparison table
- **Component Sub Text**: Subtitle or description
- **Data Information**: Additional informational text (displayed with info icon)
- **Disclaimer**: Disclaimer text displayed at the bottom

#### Companies (Repeater Field)

For each company, you can configure:

- **Company Name** *(required)*: The name of the company
- **Company ID**: Optional identifier
- **Logo URL**: URL to the company logo image
- **HQ Market**: Location/market information
- **Average Rating**: Rating from 0-5 (e.g., 4.5)
- **Total Reviews**: Number of reviews (formatted as "1.5k" for 1500+)
- **Advertised Price**: Listing fee or price information
- **Team Size**: Number of agents/team members
- **Start Year**: Year the company was founded (used to calculate years of experience)
- **License**: License information (if provided, shows verified badge)
- **Request Intro Enabled**: Toggle to enable "Get a Quote" button
- **Review Link**: URL to reviews page
- **Website URL**: Company website URL

### Button Priority Logic

The plugin intelligently displays buttons based on available data:

1. **Primary Button**: 
   - If "Request Intro Enabled" is checked → "Get a Quote"
   - Otherwise → "Read Reviews" (if review link exists)
   - Otherwise → "Visit Website" (if website URL exists)

2. **Secondary Button**:
   - Shown when primary is "Get a Quote" and review/website link exists
   - Shown when primary is "Read Reviews" and website link exists
 

### Block Alignment

The block supports:
- **Wide alignment**: Recommended for better display
- **Full width**: For full-width layouts
- **Anchor links**: Add custom anchor IDs for deep linking

## 🏗️ Architecture (What This Shows)

```
ctc-comparison-table/
├── ctc-comparison-table.php    # Main plugin file
├── templates/
│   └── ctc-block.php           # Block template with helper functions
├── assets/
│   ├── styles.css               # Responsive styles (card layout for mobile)
│   └── ctc-comparison-table.js # JavaScript (placeholder for future enhancements)
└── acf-json/
    └── group_*.json             # ACF field definitions
```

### Responsive Implementation

The responsive design is implemented entirely in CSS using:
- **CSS Grid** for desktop table layout
- **Flexbox** for card structure
- **CSS Custom Properties** for easy theming
- **Media queries** at 991px and 520px breakpoints
- **`display: contents`** to maintain semantic structure while allowing flexible layouts

## 🔒 Security

- All user inputs are properly escaped using WordPress functions (`esc_html()`, `esc_attr()`, `esc_url()`)
- Direct file access is prevented with `ABSPATH` checks
- URLs are normalized and validated
- Sanitization applied to all dynamic content

## ♿ Accessibility

- Semantic HTML5 elements (`<section>`, proper heading hierarchy)
- ARIA attributes for table structure (`role="table"`, `role="row"`, `role="cell"`)
- Screen reader text for ratings
- Keyboard navigation support
- Focus indicators for interactive elements

## 📱 Responsive Design

### Design Philosophy: Cards Over Scrollable Tables

Instead of using a horizontal scrollable table on mobile devices, this plugin implements a **card-based layout**. This design decision prioritizes user experience and follows modern mobile design best practices.

#### Why Cards Instead of Scrollable Tables?

**❌ Problems with Scrollable Tables:**
- **Poor Usability**: Horizontal scrolling is unintuitive and often goes unnoticed by users
- **Lost Context**: Users lose sight of column headers when scrolling horizontally
- **Accessibility Issues**: Screen readers struggle with horizontal scrolling tables
- **Touch Interaction**: Horizontal scrolling conflicts with natural vertical scrolling patterns
- **Data Comparison**: Difficult to compare values across columns when scrolling

**✅ Benefits of Card Layout:**
- **Better Readability**: All information for each company is visible at once
- **Natural Scrolling**: Vertical scrolling matches user expectations on mobile
- **Improved Context**: Each card is self-contained with clear visual hierarchy
- **Touch-Friendly**: Larger tap targets and easier interaction on mobile devices
- **Accessibility**: Screen readers can navigate cards more naturally
- **Visual Hierarchy**: Important information (company name, rating, CTA) is prominently displayed
- **Modern UX**: Aligns with current mobile design patterns (similar to e-commerce product cards)

#### Responsive Breakpoints

- **Desktop** (> 991px): Traditional table layout with all columns visible in a grid
  - Optimal for side-by-side comparison
  - Efficient use of horizontal space
  - Clear column headers for easy scanning

- **Tablet/Mobile** (< 991px): Card-based layout with stacked information
  - Each company displayed as an individual card
  - Information organized vertically with clear labels
  - Stats displayed in a 2-column grid within cards
  - Full-width action buttons for easy tapping

- **Small Mobile** (< 520px): Optimized single-column stats
  - Stats stack vertically for better readability
  - Adjusted spacing and font sizes
  - Maximum touch target sizes maintained
 

## 📝 License

This project is licensed under the Apache License 2.0 – see the [LICENSE](LICENSE) file for details.

## 🙏 How To Read This As A Portfolio Piece

This repo is intended to show how I approach:

- **Modern WordPress block development** (ACF V3 block API, field JSON export)
- **Front‑end implementation** (responsive layout, cards vs scrollable tables)
- **Accessibility** (roles, labels, screen‑reader text)
- **Security** (escaping, guards, URL normalization)
- **Code organization** (helpers, templates, separation of CSS/JS/PHP)

It is **not** a drop‑in product plugin, but a realistic, time‑boxed implementation that demonstrates how I think about structure, trade‑offs, and user experience under assessment constraints.

---

**Note**: This block requires Advanced Custom Fields Pro. ACF is a premium plugin available from [Advanced Custom Fields](https://www.advancedcustomfields.com/pro/).
