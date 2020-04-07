# Notification

## Props

- `is_draft`: boolean - True if draft
- `id`: string - Must be unique (JS relies on this)
- `title`: string - The title of the notification
- `content`: string - The body content of the notification
- ###### `changed`: number - Timestamp of last change made (for generating cookie)
- `isDismissible`: boolean - Whether or not the notification should appear as pre-header content and given a dismiss button

## Usage

```twig
{% include "@components/notification/notification.twig" with {
  "id": "bps-test-1"
  "title": "BPS is migrating to this new website ",
  "content": "Did we miss something? Send feedback."
} only %}
```
