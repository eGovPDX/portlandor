# Drawer

## Props

- `content`: content of the drawer
- `position`: start (default), end - Does the drawer sit on the start or end of the page (left/right for LTR languages). On larger screens, is it a start or end column.
- `open_text`: string - defaults to "Open"
- `open_info`: string - defaults to "Open Drawer"
- `close_text`: string - defaults to "Close"
- `close_info`: string - defaults to "Close Drawer"
- `drawer_id`: Unique identfier - needed so javascript can handle multiple drawers on one page. Does not need to be provided.

## Usage

```twig
{% include "@layouts/drawer/drawer.twig" with {
  content: content,
  position: 'start',
} only %}
```
