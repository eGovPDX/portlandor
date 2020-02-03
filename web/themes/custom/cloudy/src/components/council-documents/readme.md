# Council documents

## Props

- `text`*: string - Required text that is placed inside
- `type`: primary (default), secondary - Different types possible
- `isDark`: boolean

## Usage

```twig
{% include "@components/council-documents/council-documents.twig" with {
  "text": "Hello World!",
} only %}
```
