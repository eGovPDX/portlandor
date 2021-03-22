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
- `color`: string - default color is gray
- `text_wrap`: boolean - default is false
- `icon`: object { name: string, size: string, fontAwesome: boolean, brand: boolean }
- `ml`: number - default is 0
- `mr`: number - default is 0
- `mb`: number - default is 0

## Usage

```twig
{% include '@components/badge/badge.twig' with {
  text: 'Badge',
  color: 'red',
  text_wrap: true,
  ml: 2
} only %}
```
