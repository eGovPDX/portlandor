# Main menu

## Props

- `text`*: string - Required text that is placed inside
- `type`: primary (default), secondary - Different types possible
- `isDark`: boolean

## Usage

```twig
{% include "@components/main-menu/main-menu.twig" with {
  "text": "Hello World!",
} only %}
```
