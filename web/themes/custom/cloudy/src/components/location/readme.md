
**Location** is a component that holds data related to a specific place (e.g. park facility, office, or conference room), it also contains the **Link** component.

## Props

- `placename`: string - Display name of the location
- `address`: string - The address of the location
- `address2`: string - The suite name, room number, etc
- `hours`: string - Hours of operation
- `links`: array[ object{icon: object{name: string, size: string - default 'xs'}, text: string, url: string} ]

## Usage

```twig
{% include "@components/location/location.twig" with {
  placename: 'Industrial St Police Warehouse',
  address: '2619 NW Industrial St<br /> Portland, OR',
  links: [
    {
      icon: {
        name: 'map-pin',
      },
      text: 'Get Directions',
      url: '#get-directions',
    },
  ]
} only %}
```
