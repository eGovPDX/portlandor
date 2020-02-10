# Contact

## Props

- `name`*: string - Required contact name
- `title`: string
- `email`: email
- `phone`: phone
- `fax`: fax

## Usage

```twig
{% include "@components/contact/contact.twig" with {
  "name": field_title.value,
  "title": field_contact_title.value,
  "email": field_email,
  "phone": field_phone,
  "fax": field_fax,
} only %}
```
