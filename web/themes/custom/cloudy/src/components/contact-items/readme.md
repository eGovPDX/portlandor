
The *Contact Items* is a component that can contain one or more contact entities.

## Props
- `heading`: object - {}
    - `text`: string
    - `level`: number - default is 3
    - `style_override`: number - if you want a different size tag for semantic reasons but would like styling to be overridden
- `resources`: array of objects - []
    - `name`: string - the title of the resource
    - `description`: string - the description of the resource
    - `items`: array of objects - []
      - `phone`: string – the telephone number for the resource
      - `phone_note`: string (long) - notes to add context or clarification to the resource item
      - `email`: string – the email address for the resource
      - `email_note`: string (long) - notes to add context or clarification to the resource item

## Usage

```twig
{% include "@components/contact-items/contact-items.twig" with {
  heading: {
    text: "Contacts",
  },
  resources: [
    {
      name: 'Contact name',
      description: 'Contact description.',
      items: [
        {
          phone: '000-000-0000',
          phone_note: 'Phone note.',
          email: 'contact.name@domain.com',
          email_note: 'Email note.',
        },
      ],
    },
  ]
} only %}
```
