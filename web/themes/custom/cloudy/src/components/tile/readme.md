# Tile

## Props

- `text`*: string - Required text that is placed inside
- `img`: object:
    - `src`: string: path to the tile image
    - `alt`: string: Alternative text for the tile image
- `link`: string: url string
- `overline`: string: Overline text
- `is_draft`: boolean - If set to true, search result gets a "Unpublished" danger badge

## Usage

```twig
{% include "@components/tile/tile.twig" with {
  "text": "This Is An Example Of Text",
  "img":
    {
    "src": "/image/source",
    "alt": "alt text",
    },
  "link": "example.com",
  "overline": "information",
} only %}
```
