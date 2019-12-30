# Badge

## Props

- `text`*: string - Required text that is placed inside
- `type`*: success, danger, warning, info, light, dark - Required badge type
- `isPill`: boolean default(false)- If the border of the badge is rounded
- `isNavAlert`: boolean default(false) - If the alert appears in the header navigation

## Usage

```twig
{% include "@components/badge/badge.twig" with {
  "text": "Popular",
  "type": "warning",
} only %}
```
