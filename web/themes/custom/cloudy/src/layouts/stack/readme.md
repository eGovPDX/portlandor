# Stack

## Props

- `items`\*: array - Items to display
- `layout_spacing`: number - Layout bottom margin
- `item_spacing`: number - Spacing between items
- `stack`: Default aligns items horizonatally. Use `stack: true` to stack items vertically.

## Usage

```twig
{% include '@layouts/stack/stack.twig' with {
  items: [item1, item2],
  layout_spacing: 2,
  item_spacing: 2
  stack: true,
} only %}
```
