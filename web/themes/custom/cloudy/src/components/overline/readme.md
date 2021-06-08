# Overline

<ul>
  <li>block element meant to indicate type</li>
  <li>only appears above a heading, never below</li>
  <li>type is normal weight, size, and capitalized</li>
  <li>never used as a substitute for Badge</li>
  <li>Never use on a white background</li>
</ul>

## Props

- `text`: string - text

## Usage

```twig
{% include "@components/overline/overline.twig" with {
  text: "news",
} only %}
```
