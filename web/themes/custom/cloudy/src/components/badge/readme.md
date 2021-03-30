# Badge

<ul>
  <li>inline-block element meant to indicate state or status</li>
  <li>shape is rectangular with rounded corners; never pill shaped</li>
  <li>By default badge backgrouns is gray, no need to set color</li>
  <li>never used as a substitute for Overline</li>
  <li>may appear inline, but never inline with Overline</li>
  <li>may include icon</li>
</ul>

## Props

- `text`\*: string - element text
- `type`: string - default color is gray
- `icon`: object { name: string, size: string, fontAwesome: boolean, brand: boolean }
- `ml`: number - default is none
- `mr`: number - default is none
- `mb`: number - default is none

## Usage

```twig
{% include '@components/badge/badge.twig' with {
  text: 'Badge',
  type: 'danger',
  ml: 2
} only %}
```
