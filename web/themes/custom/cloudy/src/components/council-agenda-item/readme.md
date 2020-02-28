# Council agenda item

## Props

- `number`: integer - Numeric value for each item
- `type`: string - Communications, Time Certain, Consent Agenda, Regular Agenda, Four-Fifths Agenda, Suspension of the Rules, Executive Order
- `emergency`: boolean
- `time_certain`: time - the time the agenda item will be held
- `time_requested`: string - the amount of time requested, typically in minutes
- `council_document`: reference - document entity short title, includes link
- `note`: string - text to provide detail about the item
- `disposition`: string - a text description of the disposition taken pulled from list
- `dispositionNotes`: string - a text description of the disposition taken added as text
- `votes`: reference - vote entity that includes array of electeds and their vote

## Usage

```twig
{% include "@components/council-agenda-item/council-agenda-item.twig" with {
  "number": "747",
  "type": "Regular Agenda",
  "emergency": true,
  "time_certain": "9:15 AM",
  "time_requested": "15 minutes",
  "council_document": council_document,
  "note": "Second reading of agenda item 677",
  "disposition": "Referred to the Mayor's office",
  "dispositionNotes": "Referred for second reading per the longer text that goes here",
  "votes": votes,
} only %}
```
