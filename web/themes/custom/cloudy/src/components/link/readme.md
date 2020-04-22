
An opinionated **Link** with emphasis. 

## Props

- `background`: boolean - false (default)
- `standalone`: boolean - false (default)
- `icon`: object { name: string, size: string }
- `text`: string - The link text
- `url`: string - The link url

## Usage

```twig
{% include "@components/link/link.twig" with {
    background: false,
    standalone: false,
    icon: {
      name: 'email',
    },
    text: 'Send us an email',
    url: '#emails'
} only %}
```
