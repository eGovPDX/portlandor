# Tile

## Props

- `img`: object:
    - `src`: string - path to the tile image
    - `alt`: string - alternative text for the tile image
- `eyebrow`: string text
- `text`*: string - required text that is placed inside
- `link`: string - url string
- `date`: date - month day, year
- `is_draft`: boolean - if set to true, search result gets a "Unpublished" danger badge

## Usage

```twig
{% include "@components/tile/tile.twig" with {
  "img":
    {
    "src": "/image/source",
    "alt": "alt text",
    },
  "eyebrow": "text",
  "text": "This Is An Example Of Text",
  "link": "example.com",
  "date": "February 8, 2020",
  "is_draft": true
} only %}
```
