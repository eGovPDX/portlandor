## Props
- `title`: string - The heading text
- `url`: string - The click through url
- `img`: string - Image src path

## Usage

```twig
{% include "@components/chain-link/chain-link.twig" with {
    title: 'Lorem ipsum dolor sit',
    url: 'https://portland.gov/',
    img: '/src/to-the/image.jpg'
} only %}
```
