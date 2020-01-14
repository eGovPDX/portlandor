# Breadcrumb

## Props

- `items`*: array of objects - Required oject that holds the url and text for the breadcrumb link

## Usage

```twig
{% include "@components/breadcrumb/breadcrumb.twig" with {
  "items": [
    {
      "url": 'example.com',
      "text": 'breadcrumb'
    },
  ],
} only %}
```
