# Overline

<ul>
  <li>block element meant to indicate type</li>
  <li>only appears above a heading, never below</li>
  <li>type is normal weight, size, and capitalized</li>
  <li>never used as a substitute for Badge</li>
  <li>light is never used on a white background</li>
</ul>

## Props

- `text`: string - text
- `is_light`: boolean - set to true for light font color

## Usage

```twig
{% include "@components/overline/overline.twig" with {
  text: "news",
} only %}
```
