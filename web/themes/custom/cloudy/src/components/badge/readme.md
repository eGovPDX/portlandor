# Badge

<ul>
  <li>inline-block element meant to indicate state or status</li>
  <li>shape is rectangular with rounded corners; never pill shaped</li>
  <li>type is semibold and uppercase</li>
  <li>sizes will be available in small, medium, and large; medium by default</li>
  <li>never used as a substitute for Overline</li>
  <li>may appear inline, but never inline with Overline</li>
  <li>uses our 5 base colors and 3 alternate colors representing distinct statuses. Use the “subtle” color style by default and use “bold” when emphasis is needed</li>
</ul>

## Props

- `text`: string - text that is placed inside
- `type`: default, info, success, warning, danger
- `is_bold`: boolean - set to true for bold emphasis

## Usage

```twig
{% include "@components/badge/badge.twig" with {
  text: "information",
  type: "info",
} only %}
```
