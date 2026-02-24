# Styling Guide

> **RULE: Tailwind utility classes are NEVER written in `.latte` templates. All styling uses `@apply` in CSS files. Every class attribute in HTML contains only semantic class names. No exceptions.**

## Philosophy

Templates define structure. CSS defines appearance. These concerns do not mix.

Latte templates use **semantic class names only** — names that describe what an element IS, never how it looks. All visual styling lives in CSS files using Tailwind's `@apply` directive to compose utility classes into semantic rules.

This produces templates that read as structural documents and a CSS layer that serves as the single authority for all visual decisions.

## Why This Approach

### The problem with utility classes in templates

Tailwind's default approach — inline utility classes — works well in component-based JavaScript frameworks where files are small and styles are colocated with markup. Latte templates are different. They're longer, more structural, and the same HTML patterns repeat across contexts (initial page render, SSE-streamed fragments, partials). Utility-heavy markup becomes noisy, hard to scan, and creates large diffs when visual changes touch template files.

### What we gain

- **Readable templates** — every class name communicates purpose, not pixels
- **Single styling authority** — visual changes happen in CSS, never in templates
- **Clean diffs** — styling changes don't touch `.latte` files; structural changes don't touch `.css` files
- **Lean SSE payloads** — streamed message HTML carries short semantic classes, not utility strings
- **Naming as documentation** — the requirement to name elements forces intentional structure
- **Greppable design vocabulary** — `.message`, `.space-item`, `.chat-input` form a project glossary

### What we trade

- **Every element needs a name** — even one-off containers require a class. This is a feature: unnamed elements are unintentional elements.
- **More CSS rules** — the stylesheet is larger than pure-utility Tailwind. For a focused chat app with a stable layout, this is manageable.
- **Upfront work** — defining semantic classes takes more thought than pasting utilities. The payoff is long-term readability and maintainability.

## Tooling

### Tailwind CSS (Standalone CLI)

Use the Tailwind standalone CLI binary — no Node.js dependency required.

```bash
# Development (watch mode)
./tailwindcss -i src/css/app.css -o public/css/app.css --watch

# Production (minified)
./tailwindcss -i src/css/app.css -o public/css/app.css --minify
```

### File Structure

```
markotalk/
├── src/
│   └── css/
│       ├── app.css              # Entry point — imports all layers
│       ├── base.css             # Resets, typography, root variables
│       ├── layout.css           # App shell: sidebar, main area, header, footer
│       ├── components.css       # Reusable UI: buttons, inputs, badges, avatars
│       └── modules/
│           ├── message.css      # .message, .message-author, .message-body, etc.
│           ├── space.css        # .space-list, .space-item, .space-item-active, etc.
│           ├── notification.css # .notification, .notification-badge, etc.
│           ├── auth.css         # .auth-form, .auth-field, etc.
│           └── admin.css        # .admin-panel, .admin-table, etc.
├── public/
│   └── css/
│       └── app.css              # Compiled output (gitignored)
└── tailwind.config.js           # Tailwind configuration
```

### Entry Point (`src/css/app.css`)

```css
@tailwind base;
@tailwind components;
@tailwind utilities;

@import "./base.css";
@import "./layout.css";
@import "./components.css";
@import "./modules/message.css";
@import "./modules/space.css";
@import "./modules/notification.css";
@import "./modules/auth.css";
@import "./modules/admin.css";
```

## Naming Convention

### Pattern: `{module}-{element}` and `{module}-{element}-{modifier}`

Class names follow a flat BEM-inspired convention without nesting syntax:

```
.message                    — message container
.message-author             — author name within a message
.message-body               — message content area
.message-timestamp          — time indicator
.message-pinned             — modifier: pinned message variant
.message-actions            — hover actions (edit, delete, react)

.space-list                 — space sidebar list
.space-item                 — single space in the list
.space-item-active          — currently selected space
.space-item-unread          — space with unread messages

.chat-area                  — main chat container
.chat-feed                  — scrollable message feed
.chat-input                 — message input area
.chat-input-field           — the textarea itself
.chat-input-send            — send button
```

### Rules

| Rule | Example | Why |
|------|---------|-----|
| Describe what it IS, not how it looks | `.message-author` not `.bold-small-text` | Intent survives redesigns |
| Module prefix matches app module | `.message-*` for message module | Greppable, avoids collisions |
| Modifiers use a third segment | `.space-item-active` | Clear state variants |
| State classes use `is-` prefix | `.is-online`, `.is-loading`, `.is-hidden` | Distinguishes state from structure |
| Layout elements have no module prefix | `.app-shell`, `.sidebar`, `.main-content` | They belong to the app, not a module |
| No abbreviations | `.notification-badge` not `.notif-badge` | Clarity over brevity |

## CSS Authoring Rules

### Use `@apply` for all visual styling

```css
/* Correct — semantic class with @apply */
.message {
  @apply bg-white rounded-lg p-4 shadow-sm border border-gray-200 mb-2;
}

/* Wrong — utility classes in HTML */
/* <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-200 mb-2"> */
```

### Group related properties logically

```css
.message {
  /* Layout */
  @apply flex flex-col gap-1;
  /* Spacing */
  @apply p-4 mb-2;
  /* Visual */
  @apply bg-white rounded-lg shadow-sm border border-gray-200;
}
```

### Use Tailwind's `@screen` for responsive rules

```css
.sidebar {
  @apply hidden w-64 flex-shrink-0 border-r border-gray-200 bg-gray-50;
}

@screen md {
  .sidebar {
    @apply flex flex-col;
  }
}
```

### Define interactive states inline with the class

```css
.btn-primary {
  @apply px-4 py-2 rounded-md font-medium text-sm bg-indigo-600 text-white;
  @apply hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500;
  @apply disabled:opacity-50 disabled:cursor-not-allowed;
}
```

### Modifiers extend the base, never duplicate it

```css
/* Base */
.message {
  @apply bg-white rounded-lg p-4 shadow-sm border border-gray-200 mb-2;
}

/* Modifier — only the differences */
.message-pinned {
  @apply border-amber-300 bg-amber-50;
}

/* In Latte: class="message message-pinned" */
```

## Template Rules

### Templates contain only semantic classes

```latte
{* Correct *}
<div class="message {if $message->isPinned}message-pinned{/if}">
  <div class="message-header">
    <span class="message-author">{$message->user->displayName}</span>
    <time class="message-timestamp">{$message->createdAt}</time>
  </div>
  <div class="message-body">{$message->bodyHtml|noescape}</div>
</div>

{* Wrong — utility classes in template *}
<div class="bg-white rounded-lg p-4 shadow-sm border mb-2 {if $message->isPinned}border-amber-300 bg-amber-50{/if}">
```

### No inline styles

Never use `style=""` attributes. All visual decisions go through CSS classes.

### Conditional classes use Latte syntax

```latte
<li class="space-item {if $space->slug === $currentSlug}space-item-active{/if}">
```

### Layout templates define the structural shell

```latte
{* layout.latte *}
<div class="app-shell">
  <header class="app-header">
    {include 'header'}
  </header>
  <div class="app-body">
    <aside class="sidebar">
      {block sidebar}{/block}
    </aside>
    <main class="main-content">
      {block content}{/block}
    </main>
  </div>
</div>
```

## Design Tokens

Configure Tailwind's theme to define project-specific tokens. Use these instead of arbitrary values.

```js
// tailwind.config.js
module.exports = {
  content: [
    './resources/views/**/*.latte',
    './src/css/**/*.css',
  ],
  theme: {
    extend: {
      colors: {
        brand: {
          50: '#eef2ff',
          600: '#4f46e5',
          700: '#4338ca',
        },
      },
      fontSize: {
        'message': '0.9375rem',
      },
      spacing: {
        'sidebar': '16rem',
        'header': '3.5rem',
      },
    },
  },
};
```

Then reference tokens in CSS:

```css
.sidebar {
  @apply w-sidebar bg-gray-50 border-r border-gray-200;
}

.app-header {
  @apply h-header border-b border-gray-200 bg-white;
}
```

## Dark Mode (Future)

If dark mode is added later, the semantic class approach makes it straightforward:

```css
.message {
  @apply bg-white border-gray-200 text-gray-900;
}

@media (prefers-color-scheme: dark) {
  .message {
    @apply bg-gray-800 border-gray-700 text-gray-100;
  }
}
```

No template changes required — only CSS changes. This is the payoff of separating structure from appearance.
