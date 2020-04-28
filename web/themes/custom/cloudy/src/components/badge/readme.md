# Badge
<ul>
  <li>inline-block element meant to indicate state or status</li>
  <li>uses our 5 base colors and 3 alternate colors to represent distinct statuses; use "subtle" by default, use "bold" only when extra emphasis is needed</li>
</ul>

## Props
- `text`: string - text that is placed inside
- `type`: default, inform, calm, warn, danger, highlight, charm, cool
- `is_bold`: boolean - set to true for bold emphasis

## Usage
```twig
{% include "@components/badge/badge.twig" with {
  text: "information",
  type: "inform",
} only %}
```
