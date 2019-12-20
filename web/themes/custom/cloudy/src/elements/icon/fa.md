# [Font Awesome](https://github.com/FortAwesome/Font-Awesome) Icons

## Props

- `name`*: string - machine name of icon

## Usage

```twig
{% include "@elements/icon/fa.twig" with {
  name: 'arrow-right',
} only %}
```

These can be used by simply adding this HTML as well:

```html
<i class="fas fa-arrow-right"></i>
```

## Sizing

Font Awesome icons are sized based off of their `font-size`; giving one a `font-size: 42px` will result in a 42x42 sized icon. When used inline with typography the icons will pick up the size to appear relative to the font size; so when used in an `<h2>` it'll be sized appropriately.
