# Breadcrumb

## Props

- `breadcrumb`*: array of objects - Required oject that holds the url and text for the breadcrumb link

## Usage

```twig
{% include "@components/breadcrumb/breadcrumb.twig" with {
  "breadcrumb": [
    {
      "url": 'example.com',
      "text": 'breadcrumb'
    },
  ],
} only %}
```
