# Tile

## Props

- `text`*: string - Required text that is placed inside
- `img`: object:
    - `src`: string: path to the tile image
    - `alt`: string: Alternative text for the tile image
- `link`: string: url string
- `date`: string - date when tile was published
- `overline`: string: Overline text
- `isColor`: boolean default(true): Is there background color
- `is_not_published`: boolean - If set to true, search result gets a "Unpublished" danger badge

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
  "date": "May 5, 2020",
  "overline": "information",
} only %}
```
