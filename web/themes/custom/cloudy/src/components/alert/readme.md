# Alert

## Props

- `type`: string - info (default), danger, warning - Different types possible
- `title`: string - The title of the alert
- `content`: string - The body content of the alert
- `showTimestamp`: boolean - If set to true, adds the last updated timestamp to the alert
- `changed`: string - This is a timestamp of when the alert with the given nid was last updated.

## Usage

```twig
{% include "@components/alert/alert.twig" with {
  "type": "info",
  "title": "Hello World!",
  "content": "This is an example of what an alert could look like!",
} only %}
```
