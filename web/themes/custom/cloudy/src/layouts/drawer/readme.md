# Drawer

## Props

- `content`*: content of the drawer
- `postion`: left (default), right - Does the drawer sit on the left or right. On larger screen, is it a left or right column.

## Usage

```twig
{% include "@layouts/drawer/drawer.twig" with {
  "text": "Hello World!",
} only %}
```
