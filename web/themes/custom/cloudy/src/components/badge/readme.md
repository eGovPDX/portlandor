# Badge

<p>Badge is a block element meant to indicate state or status. Use “subtle” style by default and in instances where Badge may dominate the screen or overpower visually. Badge may appear inline, but never inline with Overline.</p>

<p>Badge is rectangular with rounded corners. Never pill.</p>

<p>Use “subtle” style by default and “bold” when extra emphasis is needed.</p>

## Props

- `text`: string - text that is placed inside
- `type`: default, info, win, warn, whoops, new
- `isBold`: boolean - set true for bold emphasis

## Usage

```twig
{% include "@components/badge/badge.twig" with {
  "text": "news",
  "type": "default",
  "isBold": false,
} only %}
```
