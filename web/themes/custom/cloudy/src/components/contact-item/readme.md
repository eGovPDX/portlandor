
The *Contact Item* is a component that can contain one or more contact entities.

## Props
- `name`: string - the title of the resource
- `title`: string – the title for the resource
- `email`: string – the email address for the resource
- `phone`: string – the telephone number for the resource
- `fax`: string – the fax number for the resource

## Usage
```twig
{% include "@components/contact-item/contact-item.twig" with {
  name: 'Contact name',
  title: 'Contact title',
  email: 'contact.name@domain.com',
  phone: '000-000-0000',
  fax: '000-000-0000',
} only %}
```
