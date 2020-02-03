# Council agenda item

## Props

- `number`: integer - Numeric value for each item
- `type`: string
- `status`: string
- `note`: string - text to provide detail about the item
- `disposition`: string
- `time_certain`: time - the time the agenda item will be held
- `time_requested`: string
- `council_document`: reference to document entity short title, includes link
- `votes`: vote component

## Usage

```twig
{% include "@components/council-agenda-item/council-agenda-item.twig" with {
  "text": "Hello World!",
} only %}
```
