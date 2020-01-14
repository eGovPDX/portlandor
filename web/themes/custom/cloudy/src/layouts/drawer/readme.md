# Drawer

## Props

- `content`*: content of the drawer
- `postion`: left (default), right - Does the drawer sit on the left or right. On larger screen, is it a left or right column.
- `trigger_text`: sting, defaults to "Menu"
- `drawer_id`: Unique identfier, needed so javascript can handle multiple drawers on one page. Does not need to be provided

## Usage

```twig
{% include "@layouts/drawer/drawer.twig" with {
  "content": content,
  "position": "left",
  "trigger_text": "Menu"
} only %}
```
