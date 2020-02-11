# Tile

<ul>
  <li>meant to call attention to featured content or groups</li>
  <li>can only contain featured image, Badge, Overline, a heading, and a link</li>
  <li>has a background color to give visual weight</li>
  <li>not for use with related content</li>
</ul>

## Props

- `img`: object:
    - `src`: string - path to the tile image
    - `alt`: string - alternative text for the tile image
- `overline`: string - short string to provide context
- `heading`: string - heading text
- `url`: string - url string
- `is_draft`: boolean - if set to true, search result gets a "Unpublished" danger badge

## Usage

```twig
{% include "@components/tile/tile.twig" with {
  img:
    {
    src: "/image/source",
    alt: "alt text",
    },
  overline: "news",
  heading: "This is an example of text",
  url: "example.com",
} only %}
```
