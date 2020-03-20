## Props

- `level`: string - 1 (default)
- `text`: string - The heading text

## Usage

```twig
{% include "@components/heading/heading.twig" with {
    level: 2,
    text: 'Lorem ipsum dolor sit'
} only %}
```
