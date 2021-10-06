# Tile

## Props

- `url`: string - url
- `image`: string - element markup
- `title`: string - title
- `text`: string - text

## Usage

```twig
{% include "@components/tile/tile.twig" with {
  url: '#',
  image: '<img src="path/to/image">',
  title: 'Title',
  text: 'Text'
} %}
```
