Got it! You want actual **utility classes** and **component styles** that you can drop straight into your CSS file to use across the system, similar to your `.icon-box-premium`.

Since you have a specific "Uwezo" brand palette, here is the ready-to-use CSS. I've converted your variables into functional classes:

### 1. Brand Backgrounds & Text
These allow you to apply your brand colors to any element quickly.

```css
/* Brand Primary (Purple/Magenta) */
.bg-uwezo-primary { background-color: #b72a9f !important; color: #ffffff !important; }
.bg-uwezo-secondary { background-color: #e2a7d9 !important; }
.text-uwezo-primary { color: #b72a9f !important; }

/* Interactive Hover States */
.hover-uwezo:hover {
    background-color: #811d70 !isset;
    transition: background-color 0.2s ease-in-out;
}
```

### 2. Premium Icon Box (Enhanced)
Since you liked the `.icon-box-premium`, here is a version specifically styled for your brand colors:

```css
.icon-box-uwezo {
    position: relative;
    background: #f1d5ec; /* Using your --color-accordion */
    padding: 18px;
    border-radius: 14px;
    border: 1px solid #e2a7d9;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #47103f; /* Your stepper-foreground */
    transition: all 0.3s ease;
}

.icon-box-uwezo:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(183, 42, 159, 0.15);
    border-color: #b72a9f;
}
```

### 3. Stepper & Progress Component
Using your stepper-specific colors to create a cohesive look for multi-step forms:

```css
.uwezo-stepper-item {
    background-color: #f1d5ec;
    color: #47103f;
    padding: 12px 20px;
    border-radius: 8px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    cursor: default;
}

.uwezo-stepper-item.active {
    background-color: #cd6abd;
    color: #ffffff;
}

.uwezo-stepper-item.completed {
    background-color: #e2a7d9;
    opacity: 0.8;
}
```

### 4. Custom Status Badges (OKLCH & Hex)
These match the palettes you provided earlier for high-end UI feedback:

```css
/* Red/Danger Palette */
.badge-uwezo-danger {
    background-color: #fff0f0;
    color: #871737;
    border: 1px solid #f4becd;
}

/* Yellow/Warning Palette */
.badge-uwezo-warning {
    background-color: #fff8e6;
    color: #666; /* Or a dark amber */
    border: 1px solid #ffda8a;
}

/* Global Container Constraint */
.uwezo-container {
    max-width: 1024px;
    margin-left: auto;
    margin-right: auto;
    width: 100%;
}
```

### 5. Custom Card Style
A card that fits the "Premium" look of your system:

```css
.card-uwezo {
    background: #ffffff;
    border: 1px solid #f1d5ec;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    overflow: hidden;
}

.card-uwezo-header {
    background-color: #f8fafc;
    border-bottom: 1px solid #f1d5ec;
    padding: 15px 20px;
    font-weight: 700;
    color: #471038;
}
```

### How to use:
Just paste this into your `app.css`. Now in your HTML/Blade, you can just do:
* `<div class="icon-box-uwezo"><i class="fas fa-home"></i></div>`
* `<span class="badge-uwezo-danger">Action Required</span>`

Would you like me to wrap these into a **Laravel Mix or Vite** block so they get minified for production?
