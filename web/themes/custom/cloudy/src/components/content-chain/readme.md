## Props
- `items`: array of objects
    - `title`: string - The heading text
    - `url`: string - The click through url
    - `img`: string - Image src path

## Usage

```twig
{% include "@components/content-chain/content-chain.twig" with {
  items: [
    {
      title: 'Lorem ipsum dolor sit',
      url: 'https://portland.gov/',
      img: '/src/to-the/image.jpg'
    },
    {
      title: 'Lorem ipsum dolor sit',
      url: 'https://portland.gov/',
      img: '/src/to-the/image.jpg'
    },
    {
      title: 'Lorem ipsum dolor sit',
      url: 'https://portland.gov/',
      img: '/src/to-the/image.jpg'
    },
    {
      title: 'Lorem ipsum dolor sit',
      url: 'https://portland.gov/',
      img: '/src/to-the/image.jpg'
    }
  ]
} only %}
```
