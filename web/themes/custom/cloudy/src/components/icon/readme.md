# Icon

## Props

- `name`: string - Name of icon, must be one from the "All Icons" list.
    - Is the filename of an svg in the `web/themes/custom/cloudy/src/components/icon/svgs/` folder
    - Add a new svg to that folder to add icons
- `size`: xs, s, m (default), l - How big?

## Usage

```twig
{% include "@components/icon/icon.twig" with {
  name: "email",
  size: "l",
} only %}
```
