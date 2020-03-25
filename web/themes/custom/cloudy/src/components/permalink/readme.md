
The **Permalink** component is a textual resource not meant to be clickable. It is meant purley for identification purposes in providing the user more detail about the target URI.

## Props

- `text`: string - The resource URL

## Usage

```twig
{% include "@components/permalink/permalink.twig" with {
    text: 'https://portland.gov/'
} only %}
```
