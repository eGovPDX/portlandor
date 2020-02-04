# Tile

## Props

- `img`: object:
    - `src`: string - path to the tile image
    - `alt`: string - alternative text for the tile image
- `eyebrow`: string - short string to provide context
- `heading`: string - heading text
- `link`: string - url string
- `is_draft`: boolean - if set to true, search result gets a "Unpublished" danger badge

## Usage

```twig
{% include "@components/tile/tile.twig" with {
  "img":
    {
    "src": "/image/source",
    "alt": "alt text",
    },
  "eyebrow": "news",
  "text": "This is an example of text",
  "link": "example.com",
  "is_draft": true
} only %}
```
