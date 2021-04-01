
The *Contact Item* is a component that displays a contact entity.

## Props
- `name`: string - the title of the resource
- `title`: string – the title for the resource
- `email`: string – the email address for the resource
- `phone`: string – the telephone number for the resource
- `phone_help`: string – Helpful notes to go along with the phone resource
- `tty`: string – the TTY telephone number
- `oregon_relay`: boolean - TRUE to show Oregon Relay Service resource (711)
- `fax`: string – the fax number for the resource

## Usage
```twig
{% include "@components/contact-item/contact-item.twig" with {
  name: 'Contact name',
  title: 'Contact title',
  email: 'contact.name@domain.com',
  phone: '+1-000-000-0000',
  phone_help: 'Helper text to go here',
  tty: '+1-000-000-0000',
  oregon_relay: true,
  enable_311: true,
  fax: '000-000-0000',
} only %}
```
