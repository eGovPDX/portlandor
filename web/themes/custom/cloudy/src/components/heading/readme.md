**Heading** is a utility component meant for situations where specific typographic treatment is necessary. Due to the range of complexity of implementations, this pattern is not intended for use with every heading on the page.

## Props

- `url`: string - The heading link
- `level`: number - 1 (default)
- `style`: number - Overrides the styling of the heading while maintaining the level
- `text`: string - The heading text

## Usage

```twig
{% include "@components/heading/heading.twig" with {
    url: 'http://google.com',
    level: 2,
    text: 'Lorem ipsum dolor sit'
} only %}
```
