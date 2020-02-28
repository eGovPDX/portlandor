# Council agenda session

## Props

- `title`*: string
- `datetime`: datetime
- `status`: string
- `agendaItems`: reference - includes array of agenda items

## Usage

```twig
{% include "@components/council-agenda-session/council-agenda-session.twig" with {
  "title": "Hello World!",
  "datetime": "February 5, 9:30 AM",
  "status": "Recessed",
  "agendaItems": agendaItems,
} only %}
```
