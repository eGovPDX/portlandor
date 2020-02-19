# Council document

## Props

- `type`: text -
- `title`: reference - links to the document
- `introducedBy`: text - could be a list; could link to the elected
- `bureaus`: text - could be a list; could link to the bureau

## Usage

```twig
{% include "@components/council-document/council-document.twig" with {
  "type": "Ordinance",
  "title": "This is a really long legal title that tends to be several lines long",
  "introducedBy": introduced_by,
  "bureaus": bureaus,
} only %}
```
