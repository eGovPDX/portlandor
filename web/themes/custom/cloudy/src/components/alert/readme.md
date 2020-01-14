# Alert

## Props

- `type`: string - info (default), danger, warning - Different types possible
- `title`: string - The title of the alert
- `content`: string - The body content of the alert
- `showTimestamp`: boolean - If set to true, adds the last updated timestamp to the alert
- `dismissible`: boolean - Whether or not the alert should appear as pre-header content and given a dismiss button
- `id`: string - If dismissible is true, this is a unique identifier for the alert, used for managing cookies
- `changed`: string - If dismissible is true, this is a timestamp of when the alert with the given nid was last updated. Changed against the cookie value to see if user need to be alerted again.
- `is_not_published`: boolean - If set to true, adds the `unpublished` badge

## Usage

```twig
{% include "@components/alert/alert.twig" with {
  "type": "info",
  "title": "Hello World!",
  "content": "This is an example of what an alert could look like!",
} only %}
```
